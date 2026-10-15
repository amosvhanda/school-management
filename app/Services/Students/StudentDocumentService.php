<?php

namespace App\Services\Students;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentDocumentService
{
    public function __construct(
        private FileUploadService $uploads,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForStudent(Student $student): array
    {
        return $student
            ->documents()
            ->where('school_id', $student->school_id)
            ->with('uploader:id,name')
            ->orderByDesc('id')
            ->get()
            ->map(fn (StudentDocument $doc) => $this->format($doc))
            ->all();
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array<string, mixed>>
     */
    public function upload(Student $student, array $files, ?int $uploadedBy, ?string $type = null): array
    {
        $schoolId = (int) $student->school_id;
        $uploaded = [];

        foreach ($files as $file) {
            $stored = $this->uploads->store(
                $file,
                "students/{$student->id}/documents",
                'public',
                $schoolId,
            );

            $document = StudentDocument::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'uploaded_by' => $uploadedBy,
                'name' => $stored['name'],
                'type' => $type,
                'path' => $stored['path'],
                'mime_type' => $stored['mime'] ?? $file->getMimeType(),
                'size' => $stored['size'],
            ]);

            $uploaded[] = $this->format($document);
        }

        return $uploaded;
    }

    public function destroy(Student $student, StudentDocument $document): void
    {
        if ((int) $document->student_id !== (int) $student->id) {
            abort(404);
        }

        if ((int) $document->school_id !== (int) $student->school_id) {
            abort(404);
        }

        if ($document->path) {
            Storage::disk('public')->delete($document->path);
        }

        $document->delete();
    }

    public function format(
        StudentDocument $doc
    ): array {
        return [
            'id' => $doc->id,

            'name' => $doc->name,

            'type' => $doc->type,

            'mime_type' => $doc->mime_type,

            'size' => $doc->size,

            'url' => $doc->path
                    ? Storage::disk('public')
                        ->url($doc->path)
                    : null,

            'uploaded_by' => $doc->uploader
                    ? [
                        'id' => $doc->uploader->id,

                        'name' => $doc->uploader->name,
                    ]
                    : null,

            'created_at' => $doc->created_at
                ?->toIso8601String(),
        ];
    }
}
