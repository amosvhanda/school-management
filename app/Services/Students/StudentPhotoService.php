<?php

namespace App\Services\Students;

use App\Models\Student;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

class StudentPhotoService
{
    public function __construct(
        private FileUploadService $uploads,
    ) {}

    /**
     * @return array{photo_url: string, student: Student}
     */
    public function store(Student $student, UploadedFile $file): array
    {
        $result = $this->uploads->store(
            $file,
            'student-photos',
            'public',
            (int) $student->school_id,
        );

        $student->update([
            'photo_url' => $result['url'],
        ]);

        return [
            'photo_url' => $result['url'],
            'student' => $student->fresh(),
        ];
    }
}
