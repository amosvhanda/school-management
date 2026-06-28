<?php

namespace App\Services;

use App\Models\HolidayEnrollment;
use App\Models\HolidayProgram;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HolidayProgramService
{
    public function __construct(private FinancialLedgerService $ledgerService) {}

    public function enrollStudent(HolidayProgram $program, int $studentId, ?int $enrolledBy = null): HolidayEnrollment
    {
        if (! $program->is_active) {
            throw ValidationException::withMessages([
                'holiday_program_id' => ['This holiday program is not active.'],
            ]);
        }

        $student = Student::where('school_id', $program->school_id)->findOrFail($studentId);

        return DB::transaction(function () use ($program, $student, $enrolledBy) {
            $existing = HolidayEnrollment::query()
                ->where('holiday_program_id', $program->id)
                ->where('student_id', $student->id)
                ->where('status', 'enrolled')
                ->first();

            if ($existing) {
                return $existing;
            }

            $invoice = null;
            if ((float) $program->fee_amount > 0) {
                $invoice = $this->ledgerService->createInvoice(
                    student: $student,
                    amount: (float) $program->fee_amount,
                    description: "Holiday lessons: {$program->name}",
                    createdBy: $enrolledBy,
                );
            }

            return HolidayEnrollment::create([
                'school_id' => $program->school_id,
                'holiday_program_id' => $program->id,
                'student_id' => $student->id,
                'invoice_id' => $invoice?->id,
                'status' => 'enrolled',
                'enrolled_at' => now(),
            ]);
        });
    }
}
