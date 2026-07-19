<?php

namespace App\Services;

use App\Models\School;
use App\Models\Teacher;

class StaffNumberService
{
    /**
     * Format: {SCHOOL_CODE}-EMP{####}
     * Example: MUF001-EMP0001
     */
    public function generateEmployeeNumber(int $schoolId): string
    {
        $school = School::findOrFail($schoolId);
        $rawCode = strtoupper((string) ($school->code ?: 'SCH'));
        $code = preg_replace('/[^A-Z0-9]/', '', $rawCode) ?: 'SCH';
        $prefix = "{$code}-EMP";

        $last = Teacher::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('employee_id', 'like', $prefix.'%')
            ->orderByDesc('employee_id')
            ->value('employee_id');

        $next = 1;
        if (is_string($last) && str_starts_with($last, $prefix)) {
            $tail = substr($last, strlen($prefix));
            if (ctype_digit($tail)) {
                $next = ((int) $tail) + 1;
            } else {
                $next = Teacher::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('employee_id', 'like', $prefix.'%')
                    ->count() + 1;
            }
        }

        return sprintf('%s%04d', $prefix, $next);
    }
}
