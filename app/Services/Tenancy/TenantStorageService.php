<?php

namespace App\Services\Tenancy;

class TenantStorageService
{
    /**
     * Prefix a storage directory with the tenant school scope when missing.
     * Legacy schools/{id}/… paths are rewritten to school-{id}/….
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

        // Rewrite legacy schools/{id}/… to school-{id}/…
        $legacy = "schools/{$schoolId}/";
        if (str_starts_with($directory, $legacy)) {
            return $prefix.'/'.substr($directory, strlen($legacy));
        }

        if ($directory === "schools/{$schoolId}") {
            return $prefix;
        }

        return "{$prefix}/{$directory}";
    }
}
