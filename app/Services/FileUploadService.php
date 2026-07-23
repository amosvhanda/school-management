<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Store an uploaded file and return its metadata.
     *
     * @return array{path: string, url: string, name: string, mime: string|null, size: int}
     */
    public function store(UploadedFile $file, string $directory = 'uploads', string $disk = 'public'): array
    {
        $directory = trim(preg_replace('#[^a-zA-Z0-9_/\-]#', '', $directory) ?: 'uploads', '/');
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
