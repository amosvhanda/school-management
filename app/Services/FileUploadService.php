<?php

namespace App\Services;

use App\Services\Tenancy\TenantStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function __construct(private TenantStorageService $tenantStorage) {}

    /**
     * Store an uploaded file and return its metadata.
     *
     * @return array{path: string, url: string, name: string, mime: string|null, size: int}
     */
    public function store(
        UploadedFile $file,
        string $directory = 'uploads',
        string $disk = 'public',
        ?int $schoolId = null,
    ): array {
        $directory = $this->tenantStorage->scopedDirectory($schoolId, $directory);
        $path = $file->store($directory, $disk);

        return [
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }
}
