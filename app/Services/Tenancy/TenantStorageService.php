<?php

namespace App\Services\Tenancy;

class TenantStorageService
{
    /**
     * Prefix a storage directory with the tenant school scope when missing.
     */
    public function scopedDirectory(?int $schoolId, string $directory): string
    {
        $directory = trim(preg_replace('#[^a-zA-Z0-9_/\-]#', '', $directory) ?: 'uploads', '/');

        if (! $schoolId) {
            return $directory;
        }

        $prefix = "school-{$schoolId}";

        if ($directory === $prefix || str_starts_with($directory, "{$prefix}/")) {
            return $directory;
        }

        // Legacy path used in a few document uploads.
        if (str_starts_with($directory, "schools/{$schoolId}/")) {
            return $directory;
        }

        return "{$prefix}/{$directory}";
    }
}
