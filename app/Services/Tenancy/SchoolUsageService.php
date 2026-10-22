<?php

namespace App\Services\Tenancy;

use App\Models\ClassModel;
use App\Models\Invoice;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class SchoolUsageService
{
    public function snapshot(School $school): array
    {
        $schoolId = $school->id;

        return [
            'users' => User::query()->withoutSchoolScope()->where('school_id', $schoolId)->count(),
            'students' => Student::query()->forSchool($schoolId)->count(),
            'teachers' => Teacher::query()->forSchool($schoolId)->count(),
            'classes' => ClassModel::query()->forSchool($schoolId)->count(),
            'invoices' => Invoice::query()->forSchool($schoolId)->count(),
            'domains' => $school->domains()->count(),
            'storage_bytes' => $this->estimateStorageBytes($schoolId),
            'last_activity_at' => User::query()->withoutSchoolScope()->where('school_id', $schoolId)->max('updated_at'),
        ];
    }

    private function estimateStorageBytes(int $schoolId): int
    {
        $disk = Storage::disk('public');
        $prefix = "school-{$schoolId}";
        $total = 0;

        if (! $disk->exists($prefix)) {
            return 0;
        }

        foreach ($disk->allFiles($prefix) as $path) {
            $total += (int) $disk->size($path);
        }

        return $total;
    }
}
