<?php

namespace App\Services;

use App\Models\SchoolTrip;
use App\Models\SchoolTripEnrollment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolTripService
{
    public function __construct(private FinancialLedgerService $ledgerService) {}

    public function enrollStudent(SchoolTrip $trip, int $studentId, ?int $enrolledBy = null): SchoolTripEnrollment
    {
        if (! $trip->is_active) {
            throw ValidationException::withMessages([
                'school_trip_id' => ['This trip is not active.'],
            ]);
        }

        if (! $trip->open_for_registration) {
            throw ValidationException::withMessages([
                'school_trip_id' => ['Registration for this trip is closed.'],
            ]);
        }

        $student = Student::where('school_id', $trip->school_id)->findOrFail($studentId);

        return DB::transaction(function () use ($trip, $student, $enrolledBy) {
            $existing = SchoolTripEnrollment::query()
                ->where('school_trip_id', $trip->id)
                ->where('student_id', $student->id)
                ->where('status', 'enrolled')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($trip->capacity !== null) {
                $taken = SchoolTripEnrollment::query()
                    ->where('school_trip_id', $trip->id)
                    ->where('status', 'enrolled')
                    ->lockForUpdate()
                    ->count();

                if ($taken >= (int) $trip->capacity) {
                    throw ValidationException::withMessages([
                        'school_trip_id' => ['This trip is full.'],
                    ]);
                }
            }

            $invoice = null;
            if ((float) $trip->fee_amount > 0) {
                $invoice = $this->ledgerService->createInvoice(
                    student: $student,
                    amount: (float) $trip->fee_amount,
                    description: "School trip: {$trip->name}",
                    createdBy: $enrolledBy,
                );
            }

            return SchoolTripEnrollment::create([
                'school_id' => $trip->school_id,
                'school_trip_id' => $trip->id,
                'student_id' => $student->id,
                'invoice_id' => $invoice?->id,
                'status' => 'enrolled',
                'enrolled_at' => now(),
            ]);
        });
    }
}
