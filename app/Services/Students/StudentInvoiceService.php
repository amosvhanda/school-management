<?php

namespace App\Services\Students;

use App\Models\FeeGroup;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\FinancialLedgerService;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class StudentInvoiceService
{
    public function __construct(
        private FinancialLedgerService $ledgerService,
    ) {}

    /**
     * @return array{invoice?: Invoice, invoices?: Collection<int, Invoice>, created_count: int, message: string}
     *
     * @throws InvalidArgumentException
     */
    public function createForStudent(
        Student $student,
        DateTimeImmutable $dueDate,
        ?int $createdBy,
        bool $applyDiscounts = true,
        ?int $feeGroupId = null,
        bool $combineGroup = false,
        ?float $amount = null,
        ?string $description = null,
        ?int $feeStructureId = null,
    ): array {
        $schoolId = (int) $student->school_id;

        if ($feeGroupId) {
            $group = FeeGroup::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->findOrFail($feeGroupId);

            $invoices = $this->ledgerService->applyFeeGroup(
                student: $student,
                group: $group,
                dueDate: $dueDate,
                createdBy: $createdBy,
                applyDiscounts: $applyDiscounts,
                combine: $combineGroup,
            );

            if ($invoices->isEmpty()) {
                throw new InvalidArgumentException(
                    'No fee structures found for this student\'s class in the selected fee group.'
                );
            }

            return [
                'invoice' => $invoices->first(),
                'invoices' => $invoices,
                'created_count' => $invoices->count(),
                'message' => $invoices->count() === 1
                    ? 'Invoice created successfully'
                    : "{$invoices->count()} invoices created from fee group",
            ];
        }

        $invoice = $this->ledgerService->createInvoice(
            student: $student,
            amount: (float) $amount,
            description: (string) $description,
            feeStructureId: $feeStructureId,
            dueDate: $dueDate,
            createdBy: $createdBy,
            applyDiscounts: $applyDiscounts,
        );

        return [
            'invoice' => $invoice,
            'created_count' => 1,
            'message' => 'Invoice created successfully',
        ];
    }

    /**
     * Create invoices for many students in one school. Skips missing/invalid students.
     *
     * @param  list<int>  $studentIds
     * @return array{created: int}
     */
    public function createBulk(
        int $schoolId,
        array $studentIds,
        DateTimeImmutable $dueDate,
        ?int $createdBy,
        bool $applyDiscounts = true,
        ?int $feeGroupId = null,
        bool $combineGroup = false,
        ?float $amount = null,
        ?string $description = null,
        ?int $feeStructureId = null,
    ): array {
        $students = Student::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $created = 0;

        foreach ($studentIds as $studentId) {
            $student = $students->get((int) $studentId);
            if (! $student) {
                continue;
            }

            try {
                if ($feeGroupId) {
                    $group = FeeGroup::query()
                        ->where('school_id', $student->school_id)
                        ->where('is_active', true)
                        ->find((int) $feeGroupId);

                    if (! $group) {
                        continue;
                    }

                    $created += $this->ledgerService->applyFeeGroup(
                        student: $student,
                        group: $group,
                        dueDate: $dueDate,
                        createdBy: $createdBy,
                        applyDiscounts: $applyDiscounts,
                        combine: $combineGroup,
                    )->count();

                    continue;
                }

                $this->ledgerService->createInvoice(
                    student: $student,
                    amount: (float) $amount,
                    description: (string) $description,
                    feeStructureId: $feeStructureId,
                    dueDate: $dueDate,
                    createdBy: $createdBy,
                    applyDiscounts: $applyDiscounts,
                );
                $created++;
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return ['created' => $created];
    }
}
