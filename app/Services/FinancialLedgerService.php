<?php

namespace App\Services;

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
    public function __construct(private ParentNotificationService $parentNotifications) {}

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
        int $createdBy,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($invoiceId, $amount, $method, $schoolId, $createdBy, $reference, $notes) {
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
            $this->postPaymentCredit($payment, $createdBy);

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

                Transaction::create([
                    'school_id' => $payment->school_id ?? $student->school_id,
                    'student_id' => $student->id,
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
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
        $count = Invoice::where('school_id', $schoolId)->count() + 1;

        return 'INV-'.date('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function createInvoice(
        Student $student,
        float $amount,
        string $description,
        ?int $feeStructureId = null,
        ?\DateTimeInterface $dueDate = null,
        ?int $createdBy = null,
    ): Invoice {
        $currency = $student->currency ?: $this->schoolCurrency($student->school_id);

        $invoice = Invoice::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructureId,
            'invoice_number' => $this->generateInvoiceNumber($student->school_id),
            'description' => $description,
            'amount' => $amount,
            'amount_paid' => 0,
            'balance' => $amount,
            'currency' => $currency,
            'due_date' => $dueDate ?? now()->addMonth(),
            'status' => 'pending',
        ]);

        $this->postInvoiceDebit($invoice, $createdBy);

        return $invoice;
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

        $invoices = collect();

        foreach ($query->get() as $fee) {
            $invoices->push($this->createInvoice(
                student: $student,
                amount: (float) $fee->amount,
                description: "{$contextLabel}: {$fee->category}",
                feeStructureId: $fee->id,
                createdBy: $createdBy,
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

    public function postPaymentCredit(Payment $payment, ?int $createdBy = null): Transaction
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
}
