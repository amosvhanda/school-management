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
     * School-wide document vault listing.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>, counts: array<string, int>}
     */
    public function listForSchool(int $schoolId, array $filters = []): array
    {
        $query = StudentDocument::query()
            ->where('school_id', $schoolId)
            ->with([
                'uploader:id,name',
                'student:id,first_name,last_name,full_name,student_number,class_id,photo_url',
                'student.classModel:id,name',
            ])
            ->orderByDesc('id');

        if (! empty($filters['student_id'])) {
            $query->where('student_id', (int) $filters['student_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['search'])) {
            $search = '%'.trim((string) $filters['search']).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('type', 'like', $search)
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search)
                            ->orWhere('full_name', 'like', $search)
                            ->orWhere('student_number', 'like', $search);
                    });
            });
        }
        if (! empty($filters['recent'])) {
            $query->where('created_at', '>=', now()->subDays(30));
        }
        if (! empty($filters['favourite'])) {
            $query->where('is_favourite', true);
        }
        if (! empty($filters['verified'])) {
            $query->whereNotNull('verified_at');
        }
        if (! empty($filters['pending'])) {
            $query->whereNull('verified_at');
        }
        if (! empty($filters['expired'])) {
            $query->whereNotNull('expires_at')->where('expires_at', '<', now());
        }

        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 24)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $base = StudentDocument::query()->where('school_id', $schoolId);
        $counts = [
            'all' => (clone $base)->count(),
            'recent' => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
            'favourites' => (clone $base)->where('is_favourite', true)->count(),
            'shared' => 0,
            'verified' => (clone $base)->whereNotNull('verified_at')->count(),
            'pending' => (clone $base)->whereNull('verified_at')->count(),
            'expired' => (clone $base)->whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
        ];

        return [
            'data' => collect($paginator->items())->map(fn (StudentDocument $doc) => $this->format($doc, true))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'counts' => $counts,
        ];
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
        StudentDocument $doc,
        bool $includeStudent = false
    ): array {
        $payload = [
            'id' => $doc->id,
            'student_id' => $doc->student_id,
            'name' => $doc->name,
            'type' => $doc->type,
            'mime_type' => $doc->mime_type,
            'size' => $doc->size,
            'url' => $doc->path
                    ? Storage::disk('public')
                        ->url($doc->path)
                    : null,
            'is_favourite' => (bool) ($doc->is_favourite ?? false),
            'verified_at' => $doc->verified_at?->toIso8601String(),
            'expires_at' => $doc->expires_at?->toIso8601String(),
            'uploaded_by' => $doc->uploader
                    ? [
                        'id' => $doc->uploader->id,
                        'name' => $doc->uploader->name,
                    ]
                    : null,
            'created_at' => $doc->created_at
                ?->toIso8601String(),
        ];

        if ($includeStudent && $doc->relationLoaded('student') && $doc->student) {
            $student = $doc->student;
            $payload['student'] = [
                'id' => $student->id,
                'full_name' => $student->full_name
                    ?: trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                'student_number' => $student->student_number,
                'photo_url' => $student->photo_url,
                'class' => $student->classModel?->name ?? $student->class,
            ];
        }

        return $payload;
    }
}
