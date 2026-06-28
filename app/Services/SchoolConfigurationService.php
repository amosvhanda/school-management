<?php

namespace App\Services;

use App\Models\School;
use App\Models\GradeLevel;
use App\Models\GradingScale;
use Illuminate\Support\Facades\DB;

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
     * Validate and set school currency
     */
    public function setSchoolCurrency(School $school, string $currency): bool
    {
        $allowedCurrencies = ['USD', 'ZWG'];
        
        if (!in_array($currency, $allowedCurrencies)) {
            throw new \InvalidArgumentException("Currency must be one of: " . implode(', ', $allowedCurrencies));
        }

        // Check if currency can be changed
        if ($school->currency_locked) {
            // Check if there are any payments
            $hasPayments = DB::table('payments')
                ->where('school_id', $school->id)
                ->exists();

            if ($hasPayments) {
                throw new \Exception("Currency cannot be changed after payments have been recorded. Please contact system administrator.");
            }
        }

        $school->update([
            'currency' => $currency,
            'currency_default' => $currency,
        ]);

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
