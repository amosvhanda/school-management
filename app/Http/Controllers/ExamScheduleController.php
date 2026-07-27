<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\ExamSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExamScheduleController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return ExamSchedule::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageExaminations'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['exams.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'Exam schedule';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'exam_id' => [
                'required',
                'integer',
                Rule::exists('exams', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'subject_id' => [
                'nullable',
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'room_id' => [
                'nullable',
                'integer',
                Rule::exists('rooms', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'invigilator' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'exam_id' => [
                'sometimes',
                'integer',
                Rule::exists('exams', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'subject_id' => [
                'nullable',
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'room_id' => [
                'nullable',
                'integer',
                Rule::exists('rooms', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'invigilator' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function wrapMutationsInTransaction(): bool
    {
        return true;
    }

    protected function afterStore(Request $request, Model $record): void
    {
        $this->assertEndsAfterStarts($record);
    }

    protected function afterUpdate(Request $request, Model $record): void
    {
        $this->assertEndsAfterStarts($record);
    }

    protected function assertEndsAfterStarts(Model $record): void
    {
        /** @var ExamSchedule $record */
        if (! $record->ends_at || ! $record->starts_at) {
            return;
        }

        if ($record->ends_at->lt($record->starts_at)) {
            throw ValidationException::withMessages([
                'ends_at' => ['End must be on or after the start time.'],
            ]);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $query = $this->schoolQuery($request)
            ->with([
                'exam:id,name',
                'classModel:id,name',
                'subject:id,name',
                'room:id,name',
            ]);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->integer('exam_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->integer('class_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $q) use ($search) {
                $q->where('invigilator', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $this->orderIndex($query);

        return $this->indexResponse($request, $query);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);

        $record = $this->schoolQuery($request)
            ->with([
                'exam:id,name',
                'classModel:id,name',
                'subject:id,name',
                'room:id,name',
            ])
            ->findOrFail($id);

        return response()->json(['data' => $record]);
    }

    protected function orderIndex(Builder $query): Builder
    {
        return $query->orderBy('starts_at');
    }
}
