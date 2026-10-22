<?php

namespace App\Http\Controllers;

use App\Http\Concerns\HandlesResourceQueries;
use App\Http\Requests\Api\V1\Exam\RecordExamResultsRequest;
use App\Http\Requests\Api\V1\Exam\StoreExamRequest;
use App\Http\Requests\Api\V1\Exam\UpdateExamRequest;
use App\Http\Resources\Api\V1\ExamResource;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\Domain\SchoolDomainRules;
use App\Services\ExamAccessService;
use App\Services\ParentNotificationService;
use App\Services\Platform\MarkModerationLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    use HandlesResourceQueries;

    public function __construct(
        private ParentNotificationService $parentNotifications,
        private AuditService $auditService,
        private MarkModerationLockService $markLock,
        private ExamAccessService $examAccess,
    ) {}

    /**
     * Get all exams for the school
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Exam::class);
        $user = $request->user();
        $schoolId = $user->school_id;

        $base = Exam::query()->where('school_id', $schoolId);
        $base = $this->examAccess->scopeVisibleExams($base, $user);

        return $this->paginateResource($request, Exam::class, ExamResource::class, [
            'base' => $base,
            'filters' => [
                'term_id',
                'grade_level_id',
                'subject_id',
                'academic_year',
                'search',
            ],
            'search_columns' => ['name', 'description'],
            'sorts' => ['exam_date', 'created_at', 'name', 'academic_year'],
            'includes' => ['term', 'gradeLevel', 'subject'],
            'default_sort' => '-exam_date',
            'with' => ['term', 'gradeLevel', 'subject'],
            'with_count' => ['examResults'],
            'default_per_page' => 50,
            'max_all' => 500,
        ]);
    }

    /**
     * Get a specific exam
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $exam = Exam::where('school_id', $schoolId)
            ->with(['term', 'gradeLevel', 'subject', 'examResults.student'])
            ->findOrFail($id);

        $this->authorize('view', $exam);

        // Get all students in the grade level
        $students = Student::where('school_id', $schoolId)
            ->where('grade_level_id', $exam->grade_level_id)
            ->where('status', 'active')
            ->get();

        $exam->students = $students;

        return response()->json([
            'data' => $exam,
        ]);
    }

    /**
     * Create a new exam
     */
    public function store(StoreExamRequest $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validated();

        app(SchoolDomainRules::class)->assertSubjectAvailableForGrade(
            $schoolId,
            (int) $data['grade_level_id'],
            (int) $data['subject_id'],
        );

        $exam = Exam::create([
            ...$data,
            'school_id' => $schoolId,
            'is_published' => $request->boolean('is_published', false),
        ]);

        return response()->json([
            'message' => 'Exam created successfully',
            'data' => $exam->load(['term', 'gradeLevel', 'subject']),
        ], 201);
    }

    /**
     * Update an exam
     */
    public function update(UpdateExamRequest $request, $id)
    {
        $exam = Exam::where('school_id', $request->user()->school_id)
            ->findOrFail($id);

        $exam->update($request->validated());

        return response()->json([
            'message' => 'Exam updated successfully',
            'data' => $exam->fresh()->load(['term', 'gradeLevel', 'subject']),
        ]);
    }

    /**
     * Delete an exam
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $exam = Exam::where('school_id', $schoolId)
            ->findOrFail($id);

        $this->authorize('delete', $exam);

        // Delete exam results
        $exam->examResults()->delete();

        $exam->delete();

        return response()->json([
            'message' => 'Exam deleted successfully',
        ]);
    }

    /**
     * Record exam results for students
     */
    public function recordResults(RecordExamResultsRequest $request, $id)
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $exam = $request->exam();

        $this->examAccess->assertExamOpenForEntry($exam);

        $results = $request->validated('results');

        DB::transaction(function () use ($exam, $results, $schoolId, $user) {
            foreach ($results as $resultData) {
                $student = Student::where('school_id', $schoolId)
                    ->where('grade_level_id', $exam->grade_level_id)
                    ->findOrFail($resultData['student_id']);

                ExamResult::updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'school_id' => $schoolId,
                        'subject_id' => $exam->subject_id,
                        'marks_obtained' => $resultData['marks_obtained'],
                        'total_marks' => $exam->total_marks,
                        'remarks' => $resultData['remarks'] ?? null,
                        'status' => 'draft',
                        'entered_by' => $user->id,
                        'approved_by' => null,
                        'approved_at' => null,
                    ]
                );
            }
        });

        $this->auditService->log(
            module: 'examination',
            action: 'results_entered',
            auditable: $exam,
            description: "Exam results entered for {$exam->name}",
            metadata: ['results_count' => count($results)],
        );

        return response()->json([
            'message' => 'Exam results recorded successfully',
            'data' => $exam->fresh()->load('examResults.student'),
        ]);
    }

    public function approveResults(Request $request, $id)
    {
        $user = $request->user();
        $exam = Exam::where('school_id', $user->school_id)->findOrFail($id);
        $this->authorize('approveResults', $exam);

        $updated = ExamResult::where('exam_id', $exam->id)->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $exam->update([
            'results_approved_at' => now(),
            'results_approved_by' => $user->id,
        ]);

        $this->markLock->lockExamResults($exam->id, $user->school_id);

        $this->auditService->log(
            module: 'examination',
            action: 'results_approved',
            auditable: $exam,
            description: "Approved {$updated} exam result(s) for {$exam->name}",
        );

        return response()->json([
            'message' => 'Exam results approved successfully',
            'data' => $exam->fresh()->load('examResults'),
        ]);
    }

    public function publish(Request $request, $id)
    {
        $user = $request->user();
        $exam = Exam::where('school_id', $user->school_id)->findOrFail($id);
        $this->authorize('publish', $exam);

        $pending = ExamResult::where('exam_id', $exam->id)->where('status', 'draft')->count();
        if ($pending > 0 && ! $request->boolean('force', false)) {
            return response()->json([
                'message' => 'All results must be approved before publication.',
                'pending_count' => $pending,
            ], 422);
        }

        $exam->update(['is_published' => true]);

        if ($request->boolean('notify_parents', true)) {
            $this->parentNotifications->notifyExamResultsPublished($exam->fresh());
        }

        $this->auditService->log(
            module: 'examination',
            action: 'published',
            auditable: $exam,
            description: "Published exam results for {$exam->name}",
        );

        return response()->json([
            'message' => 'Exam results published successfully',
            'data' => $exam->fresh(),
        ]);
    }

    public function analytics(Request $request)
    {
        $this->authorize('viewAny', Exam::class);
        $schoolId = $request->user()?->school_id;
        if (! $schoolId) {
            abort(403, 'User is not associated with a school.');
        }

        $query = ExamResult::query()
            ->where('school_id', $schoolId)
            ->with(['exam:id,name,academic_year', 'subject:id,name']);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        $results = $query->get();

        $byExam = $results->groupBy('exam_id')->map(function ($rows) {
            $avg = $rows->avg(fn ($r) => $r->total_marks > 0 ? ($r->marks_obtained / $r->total_marks) * 100 : 0);

            return [
                'exam_id' => $rows->first()->exam_id,
                'exam_name' => $rows->first()->exam?->name,
                'academic_year' => $rows->first()->exam?->academic_year,
                'students' => $rows->count(),
                'average_percent' => round($avg, 1),
            ];
        })->values();

        $bySubject = $results->groupBy('subject_id')->map(function ($rows) {
            $avg = $rows->avg(fn ($r) => $r->total_marks > 0 ? ($r->marks_obtained / $r->total_marks) * 100 : 0);

            return [
                'subject' => $rows->first()->subject?->name,
                'records' => $rows->count(),
                'average_percent' => round($avg, 1),
            ];
        })->values();

        return response()->json([
            'data' => [
                'total_records' => $results->count(),
                'by_exam' => $byExam,
                'by_subject' => $bySubject,
            ],
        ]);
    }
}
