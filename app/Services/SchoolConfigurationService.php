<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\GradeLevel;
use App\Models\GradingScale;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolConfigurationService
{
    /**
     * Initialize default grade levels for a school
     */
    public function initializeDefaultGradeLevels(School $school, array $gradeLevels = null): void
    {
        if ($gradeLevels === null) {
            // Default: Grade 1-7
            $gradeLevels = [
                ['name' => 'Grade 1', 'code' => 'G1', 'order' => 1],
                ['name' => 'Grade 2', 'code' => 'G2', 'order' => 2],
                ['name' => 'Grade 3', 'code' => 'G3', 'order' => 3],
                ['name' => 'Grade 4', 'code' => 'G4', 'order' => 4],
                ['name' => 'Grade 5', 'code' => 'G5', 'order' => 5],
                ['name' => 'Grade 6', 'code' => 'G6', 'order' => 6],
                ['name' => 'Grade 7', 'code' => 'G7', 'order' => 7],
            ];
        }

        foreach ($gradeLevels as $level) {
            GradeLevel::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $level['name'],
                ],
                [
                    'code' => $level['code'] ?? null,
                    'order' => $level['order'] ?? 0,
                    'description' => $level['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialize default grading scale for a school
     */
    public function initializeDefaultGradingScale(School $school, array $gradingScale = null): void
    {
        if ($gradingScale === null) {
            // Default: A=80-100, B=70-79, C=60-69, D=50-59, E=0-49
            $gradingScale = [
                ['grade' => 'A', 'min_score' => 80, 'max_score' => 100, 'description' => 'Excellent', 'order' => 1],
                ['grade' => 'B', 'min_score' => 70, 'max_score' => 79, 'description' => 'Good', 'order' => 2],
                ['grade' => 'C', 'min_score' => 60, 'max_score' => 69, 'description' => 'Satisfactory', 'order' => 3],
                ['grade' => 'D', 'min_score' => 50, 'max_score' => 59, 'description' => 'Pass', 'order' => 4],
                ['grade' => 'E', 'min_score' => 0, 'max_score' => 49, 'description' => 'Fail', 'order' => 5],
            ];
        }

        foreach ($gradingScale as $scale) {
            GradingScale::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'grade' => $scale['grade'],
                ],
                [
                    'min_score' => $scale['min_score'],
                    'max_score' => $scale['max_score'],
                    'description' => $scale['description'] ?? null,
                    'order' => $scale['order'] ?? 0,
                ]
            );
        }
    }

    /**
     * Set the school-wide fees currency used across invoices, fees, store, and trips.
     */
    public function setSchoolCurrency(School $school, string $currency): bool
    {
        $currency = strtoupper(trim($currency));

        if (! $school->validateCurrency($currency)) {
            throw ValidationException::withMessages([
                'currency' => ['Fees currency must be USD or ZWG.'],
            ]);
        }

        $current = $school->getDefaultCurrency();
        $hasPayments = Payment::query()
            ->where('school_id', $school->id)
            ->where('status', '!=', 'reversed')
            ->exists();

        if ($current !== $currency && ($school->currency_locked || $hasPayments)) {
            throw ValidationException::withMessages([
                'currency' => [
                    'Fees currency cannot be changed after payments have been recorded. Contact a system administrator if you need help.',
                ],
            ]);
        }

        DB::transaction(function () use ($school, $currency, $hasPayments) {
            $school->update([
                'currency' => $currency,
                'currency_default' => $currency,
                'currency_locked' => $hasPayments || (bool) $school->currency_locked,
            ]);

            Student::query()
                ->where('school_id', $school->id)
                ->update(['currency' => $currency]);

            // Keep typed settings bag in sync without going through SchoolSettingsService::set
            // (avoids recursion when settings updates trigger this method).
            $setting = SchoolSetting::withoutGlobalScopes()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'group' => 'regional',
                    'key' => 'currency',
                ],
                [
                    'type' => 'string',
                    'is_public' => true,
                ]
            );
            $setting->setTypedValue($currency);
            $setting->save();
        });

        return true;
    }

    /**
     * Get grade letter for a score based on school's grading scale
     */
    public function getGradeForScore(School $school, float $score): ?string
    {
        return GradingScale::getGradeForScore($school->id, $score);
    }
}
