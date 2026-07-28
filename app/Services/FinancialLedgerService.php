<?php

namespace App\Services;

use App\Models\FeeGroup;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialLedgerService
{
    public function __construct(
        private ParentNotificationService $parentNotifications,
        private FeeDiscountService $feeDiscounts,
    ) {}

    public function schoolCurrency(int $schoolId): string
    {
        $school = \App\Models\School::find($schoolId);

        return $school?->getDefaultCurrency() ?? $school?->currency ?? 'USD';
    }

    public function resolveInvoiceStatus(Invoice $invoice): string
    {
        $balance = (float) $invoice->balance;

        if ($balance <= 0) {
            return 'paid';
        }

        if ((float) $invoice->amount_paid > 0) {
            return 'partial';
        }

        if ($invoice->due_date && $invoice->due_date->isPast()) {
            return 'overdue';
        }

        return 'pending';
    }

    public function syncOverdueInvoices(?int $schoolId = null): int
    {
        return Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', ['pending', 'partial'])
            ->where('balance', '>', 0)
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    public function applyPaymentToInvoice(Invoice $invoice, float $amount): Invoice
    {
        $invoice->amount_paid = round((float) $invoice->amount_paid + $amount, 2);
        $invoice->balance = max(0, round((float) $invoice->amount - (float) $invoice->amount_paid, 2));
        $invoice->status = $this->resolveInvoiceStatus($invoice);
        $invoice->save();

        return $invoice;
    }

    /**
     * Record a payment against an invoice inside a single DB transaction.
     * Uses row locks to stay consistent under concurrent collections.
     */
    public function recordPayment(
        int $invoiceId,
        float $amount,
        string $method,
        int $schoolId,
        ?int $createdBy = null,
        ?string $reference = null,
        ?string $notes = null,
        ?int $incomeHeadId = null,
    ): Payment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($invoiceId, $amount, $method, $schoolId, $createdBy, $reference, $notes, $incomeHeadId) {
            $invoice = Invoice::query()
                ->where('school_id', $schoolId)
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            if (in_array($invoice->status, ['paid'], true)) {
                throw new InvalidArgumentException('This invoice is already fully paid.');
            }

            if ($amount > (float) $invoice->balance + 0.001) {
                throw \App\Exceptions\DomainException::make(
                    'payment_exceeds_balance',
                    'Fee payment exceeds outstanding balance.',
                    ['amount' => [
                        'Payment amount exceeds the invoice balance of '.number_format((float) $invoice->balance, 2),
                    ]],
                );
            }

            if ($reference) {
                $duplicate = Payment::query()
                    ->where('school_id', $schoolId)
                    ->where('reference', $reference)
                    ->where('status', 'completed')
                    ->exists();

                if ($duplicate) {
                    throw new InvalidArgumentException('A completed payment with this reference already exists.');
                }
            }

            if ($incomeHeadId) {
                $headOk = \App\Models\IncomeHead::query()
                    ->where('school_id', $schoolId)
                    ->whereKey($incomeHeadId)
                    ->where('is_active', true)
                    ->exists();
                if (! $headOk) {
                    throw new InvalidArgumentException('Income head is inactive or not found for this school.');
                }
            }

            $payment = Payment::create([
                'student_id' => $invoice->student_id,
                'invoice_id' => $invoice->id,
                'school_id' => $schoolId,
                'amount' => round($amount, 2),
                'currency' => $invoice->currency,
                'method' => $method,
                'reference' => $reference,
                'notes' => $notes,
                'status' => 'completed',
                'date' => now(),
                'created_by' => $createdBy,
            ]);

            $this->applyPaymentToInvoice($invoice, $amount);
            $this->postPaymentCredit($payment, $createdBy, $incomeHeadId);

            return $payment->fresh(['student', 'invoice']);
        });
    }

    /**
     * Reverse a completed payment and restore invoice + student balances.
     */
    public function reversePayment(Payment $payment, ?int $reversedBy = null, ?string $reason = null): Payment
    {
        if ($payment->status === 'reversed') {
            throw new InvalidArgumentException('Payment already reversed.');
        }

        return DB::transaction(function () use ($payment, $reversedBy, $reason) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === 'reversed') {
                throw new InvalidArgumentException('Payment already reversed.');
            }

            $payment->status = 'reversed';
            if ($reason) {
                $payment->notes = trim(($payment->notes ? $payment->notes."\n" : '').'Reversal: '.$reason);
            }
            $payment->save();

            if ($payment->invoice_id) {
                $invoice = Invoice::query()->lockForUpdate()->find($payment->invoice_id);
                if ($invoice) {
                    $invoice->amount_paid = max(0, round((float) $invoice->amount_paid - (float) $payment->amount, 2));
                    $invoice->balance = max(0, round((float) $invoice->amount - (float) $invoice->amount_paid, 2));
                    $invoice->status = $this->resolveInvoiceStatus($invoice);
                    $invoice->save();
                }
            }

            $student = Student::query()->lockForUpdate()->find($payment->student_id);
            if ($student) {
                $newBalance = round($this->currentStudentBalance($student->id) + (float) $payment->amount, 2);
                $student->update(['balance' => $newBalance]);

                $incomeHeadId = Transaction::query()
                    ->where('payment_id', $payment->id)
                    ->where('type', 'payment')
                    ->value('income_head_id');

                Transaction::create([
                    'school_id' => $payment->school_id ?? $student->school_id,
                    'student_id' => $student->id,
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                    'income_head_id' => $incomeHeadId ? (int) $incomeHeadId : null,
                    'type' => 'reversal',
                    'category' => 'student',
                    'description' => $reason ? "Payment reversed: {$reason}" : 'Payment reversed',
                    'reference' => $payment->reference,
                    'debit' => $payment->amount,
                    'credit' => 0,
                    'balance' => $newBalance,
                    'currency' => $payment->currency,
                    'status' => 'completed',
                    'payment_method' => $payment->method,
                    'created_by' => $reversedBy,
                ]);

                $this->parentNotifications->notifyFeeStatement(
                    $student->fresh(),
                    $newBalance,
                    $payment->currency,
                );
            }

            return $payment->fresh(['student', 'invoice']);
        });
    }

    public function currentStudentBalance(int $studentId): float
    {
        $student = Student::find($studentId);

        return (float) ($student?->balance ?? 0);
    }

    public function generateInvoiceNumber(int $schoolId): string
    {
        $year = date('Y');
        $prefix = 'INV-'.$year.'-';

        // Caller must run inside a DB transaction so lockForUpdate serializes concurrent creates.
        $latest = Invoice::query()
            ->where('school_id', $schoolId)
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = 1;
        if (is_string($latest) && preg_match('/^INV-\d{4}-(\d+)$/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function createInvoice(
        Student $student,
        float $amount,
        string $description,
        ?int $feeStructureId = null,
        ?\DateTimeInterface $dueDate = null,
        ?int $createdBy = null,
        bool $applyDiscounts = true,
        ?int $feeGroupId = null,
        ?int $feeCategoryId = null,
        ?float $forcedOriginalAmount = null,
        ?float $forcedDiscountAmount = null,
        ?int $forcedDiscountId = null,
    ): Invoice {
        return DB::transaction(function () use (
            $student,
            $amount,
            $description,
            $feeStructureId,
            $dueDate,
            $createdBy,
            $applyDiscounts,
            $feeGroupId,
            $feeCategoryId,
            $forcedOriginalAmount,
            $forcedDiscountAmount,
            $forcedDiscountId,
        ) {
            $currency = $student->currency ?: $this->schoolCurrency($student->school_id);
            $structure = null;
            if ($feeStructureId) {
                $structure = FeeStructure::query()
                    ->where('school_id', $student->school_id)
                    ->find($feeStructureId);
                if (! $structure) {
                    throw new InvalidArgumentException('Fee structure not found for this school.');
                }
            }

            $categoryId = $feeCategoryId ?? $this->feeDiscounts->feeCategoryIdFromStructure($structure);
            $originalAmount = round(max(0, $forcedOriginalAmount ?? $amount), 2);
            $discountAmount = 0.0;
            $discountId = null;
            $finalAmount = $originalAmount;
            $finalDescription = $description;

            if ($forcedOriginalAmount !== null) {
                $discountAmount = round(max(0, $forcedDiscountAmount ?? 0), 2);
                $discountId = $forcedDiscountId;
                $finalAmount = round(max(0, $originalAmount - $discountAmount), 2);
            } elseif ($applyDiscounts && $originalAmount > 0) {
                // Discount windows are evaluated on issue date, not due date.
                $applied = $this->feeDiscounts->applyBestDiscount($student, $originalAmount, $categoryId, now());
                $finalAmount = $applied['amount'];
                $discountAmount = $applied['discount_amount'];
                $discountId = $applied['discount']?->id;
                if ($applied['discount'] && $discountAmount > 0) {
                    $finalDescription = trim($description.' (discount: '.$applied['discount']->name.')');
                }
            }

            $invoice = Invoice::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'fee_structure_id' => $structure?->id,
                'fee_discount_id' => $discountId,
                'fee_group_id' => $feeGroupId,
                'invoice_number' => $this->generateInvoiceNumber((int) $student->school_id),
                'description' => $finalDescription,
                'amount' => $finalAmount,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'amount_paid' => 0,
                'balance' => $finalAmount,
                'currency' => $currency,
                'due_date' => $dueDate ?? now()->addMonth(),
                'status' => 'pending',
            ]);

            $this->postInvoiceDebit($invoice, $createdBy);

            return $invoice;
        });
    }

    /**
     * Invoice all fee structures in a fee group that match the student's class.
     *
     * @return Collection<int, Invoice>
     */
    public function applyFeeGroup(
        Student $student,
        FeeGroup $group,
        ?\DateTimeInterface $dueDate = null,
        ?int $createdBy = null,
        bool $applyDiscounts = true,
        bool $combine = false,
    ): Collection {
        if (! $group->is_active) {
            return collect();
        }

        $categoryIds = $group->categories()->pluck('fee_categories.id');
        if ($categoryIds->isEmpty()) {
            return collect();
        }

        $structures = $this->resolveFeeStructuresForCategories(
            schoolId: (int) $student->school_id,
            classId: $student->class_id ? (int) $student->class_id : null,
            categoryIds: $categoryIds->all(),
        );

        if ($structures->isEmpty()) {
            return collect();
        }

        if ($combine) {
            $labels = [];
            $discountNames = [];
            $totalOriginal = 0.0;
            $totalDiscount = 0.0;
            $singleDiscountId = null;

            foreach ($structures as $fee) {
                $original = round((float) $fee->amount, 2);
                $totalOriginal += $original;
                if ($fee->category) {
                    $labels[] = (string) $fee->category;
                }

                if (! $applyDiscounts) {
                    continue;
                }

                $applied = $this->feeDiscounts->applyBestDiscount(
                    $student,
                    $original,
                    $fee->fee_category_id ? (int) $fee->fee_category_id : null,
                    now(),
                );
                $totalDiscount += $applied['discount_amount'];
                if ($applied['discount'] && $applied['discount_amount'] > 0) {
                    $discountNames[] = $applied['discount']->name;
                    $singleDiscountId = $structures->count() === 1 ? $applied['discount']->id : null;
                }
            }

            $labelText = collect($labels)->filter()->unique()->implode(', ');
            $description = "Fee group {$group->name}".($labelText ? ": {$labelText}" : '');
            if ($discountNames !== []) {
                $description .= ' (discount: '.implode(', ', array_values(array_unique($discountNames))).')';
            }

            $invoice = $this->createInvoice(
                student: $student,
                amount: max(0, $totalOriginal - $totalDiscount),
                description: $description,
                feeStructureId: null,
                dueDate: $dueDate,
                createdBy: $createdBy,
                applyDiscounts: false,
                feeGroupId: $group->id,
                forcedOriginalAmount: $totalOriginal,
                forcedDiscountAmount: $totalDiscount,
                forcedDiscountId: $singleDiscountId,
            );

            return collect([$invoice]);
        }

        $invoices = collect();
        foreach ($structures as $fee) {
            $invoices->push($this->createInvoice(
                student: $student,
                amount: (float) $fee->amount,
                description: "{$group->name}: {$fee->category}",
                feeStructureId: $fee->id,
                dueDate: $dueDate,
                createdBy: $createdBy,
                applyDiscounts: $applyDiscounts,
                feeGroupId: $group->id,
                feeCategoryId: $fee->fee_category_id ? (int) $fee->fee_category_id : null,
            ));
        }

        return $invoices;
    }

    /**
     * Prefer class-specific fee structures; fall back to school-wide (null class) per category.
     *
     * @param  list<int|string>  $categoryIds
     * @return Collection<int, FeeStructure>
     */
    public function resolveFeeStructuresForCategories(int $schoolId, ?int $classId, array $categoryIds): Collection
    {
        if ($categoryIds === []) {
            return collect();
        }

        $rows = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->whereIn('fee_category_id', $categoryIds)
            ->where(function ($q) use ($classId) {
                if ($classId) {
                    $q->where('class_id', $classId)->orWhereNull('class_id');
                } else {
                    $q->whereNull('class_id');
                }
            })
            ->orderBy('category')
            ->get();

        return $rows
            ->groupBy(fn (FeeStructure $row) => $row->fee_category_id ?: 'name:'.$row->category)
            ->map(function (Collection $group) use ($classId) {
                if ($classId) {
                    $specific = $group->firstWhere('class_id', $classId);
                    if ($specific) {
                        return $specific;
                    }
                }

                return $group->firstWhere('class_id', null) ?? $group->first();
            })
            ->filter()
            ->values();
    }

    /**
     * Apply all fee structures for a student's class (admission, promotion, repetition).
     *
     * @return Collection<int, Invoice>
     */
    public function applyClassFeeStructures(
        Student $student,
        ?int $classId,
        string $contextLabel,
        ?int $createdBy = null,
        array $categoryFilter = [],
    ): Collection {
        if (! $classId) {
            return collect();
        }

        $query = FeeStructure::query()
            ->where('school_id', $student->school_id)
            ->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)
                    ->orWhereNull('class_id');
            });

        if ($categoryFilter !== []) {
            $query->whereIn('category', $categoryFilter);
        }

        $rows = $query->orderBy('category')->get();
        $structures = $rows
            ->groupBy(fn (FeeStructure $row) => $row->fee_category_id ?: 'name:'.$row->category)
            ->map(function (Collection $group) use ($classId) {
                $specific = $group->firstWhere('class_id', $classId);
                if ($specific) {
                    return $specific;
                }

                return $group->firstWhere('class_id', null) ?? $group->first();
            })
            ->filter()
            ->values();

        $invoices = collect();

        foreach ($structures as $fee) {
            $invoices->push($this->createInvoice(
                student: $student,
                amount: (float) $fee->amount,
                description: "{$contextLabel}: {$fee->category}",
                feeStructureId: $fee->id,
                createdBy: $createdBy,
                applyDiscounts: true,
                feeCategoryId: $fee->fee_category_id ? (int) $fee->fee_category_id : null,
            ));
        }

        return $invoices;
    }

    public function postInvoiceDebit(Invoice $invoice, ?int $createdBy = null): Transaction
    {
        $student = $invoice->student ?? Student::find($invoice->student_id);
        $newBalance = $this->currentStudentBalance($invoice->student_id) + (float) $invoice->amount;

        if ($student) {
            $student->update([
                'balance' => $newBalance,
                'currency' => $invoice->currency,
            ]);
        }

        $transaction = Transaction::create([
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'invoice_id' => $invoice->id,
            'type' => 'fee_applied',
            'category' => 'student',
            'description' => $invoice->description,
            'reference' => $invoice->invoice_number,
            'invoice_number' => $invoice->invoice_number,
            'debit' => $invoice->amount,
            'credit' => 0,
            'balance' => $newBalance,
            'currency' => $invoice->currency,
            'status' => 'completed',
            'created_by' => $createdBy,
        ]);

        if ($student) {
            $this->parentNotifications->notifyFeeStatement(
                $student->fresh(),
                $newBalance,
                $invoice->currency,
            );
        }

        return $transaction;
    }

    public function postPaymentCredit(Payment $payment, ?int $createdBy = null, ?int $incomeHeadId = null): Transaction
    {
        $student = $payment->student ?? Student::find($payment->student_id);
        $newBalance = max(0, $this->currentStudentBalance($payment->student_id) - (float) $payment->amount);

        if ($student) {
            $student->update(['balance' => $newBalance]);
        }

        $transaction = Transaction::create([
            'school_id' => $payment->school_id ?? $student?->school_id,
            'student_id' => $payment->student_id,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'income_head_id' => $incomeHeadId,
            'type' => 'payment',
            'category' => 'student',
            'description' => 'Payment received',
            'reference' => $payment->reference,
            'debit' => 0,
            'credit' => $payment->amount,
            'balance' => $newBalance,
            'currency' => $payment->currency,
            'status' => 'completed',
            'payment_method' => $payment->method,
            'created_by' => $createdBy ?? $payment->created_by,
        ]);

        if ($student) {
            $this->parentNotifications->notifyFeeStatement(
                $student->fresh(),
                $newBalance,
                $payment->currency,
            );
        }

        return $transaction;
    }

    /**
     * Record cash received that is not a student fee payment (e.g. inventory till sales).
     */
    public function recordCashIncome(
        int $schoolId,
        float $amount,
        string $currency,
        string $category,
        string $description,
        string $paymentMethod,
        ?string $reference = null,
        ?int $createdBy = null,
        ?int $studentId = null,
        ?string $notes = null,
    ): Transaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Income amount must be greater than zero.');
        }

        return Transaction::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'type' => 'income',
            'category' => $category,
            'description' => $description,
            'reference' => $reference,
            'debit' => 0,
            'credit' => round($amount, 2),
            'balance' => round($amount, 2),
            'currency' => $currency,
            'status' => 'completed',
            'payment_method' => $paymentMethod,
            'created_by' => $createdBy,
            'notes' => $notes,
        ]);
    }

    /**
     * Record cash leaving the school (procurement, petty cash, utilities, etc.).
     */
    public function recordExpense(
        int $schoolId,
        float $amount,
        string $currency,
        string $category,
        string $description,
        string $paymentMethod,
        ?string $reference = null,
        ?int $createdBy = null,
        ?string $notes = null,
        ?int $expenseHeadId = null,
    ): Transaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Expense amount must be greater than zero.');
        }

        if ($expenseHeadId) {
            $headOk = \App\Models\ExpenseHead::query()
                ->where('school_id', $schoolId)
                ->whereKey($expenseHeadId)
                ->where('is_active', true)
                ->exists();
            if (! $headOk) {
                throw new InvalidArgumentException('Expense head is inactive or not found for this school.');
            }
        }

        return Transaction::create([
            'school_id' => $schoolId,
            'student_id' => null,
            'type' => 'expense',
            'category' => $category,
            'description' => $description,
            'reference' => $reference,
            'debit' => round($amount, 2),
            'credit' => 0,
            'balance' => -round($amount, 2),
            'currency' => $currency,
            'status' => 'completed',
            'payment_method' => $paymentMethod,
            'created_by' => $createdBy,
            'notes' => $notes,
            'expense_head_id' => $expenseHeadId,
        ]);
    }
}
