<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Term;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\Student;
use App\Models\GradingScale;
use App\Services\AuditService;
use App\Services\ExamAccessService;
use App\Services\ParentNotificationService;
use App\Services\Platform\MarkModerationLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
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
        $user = $request->user();
        $schoolId = $user->school_id;

        $query = Exam::where('school_id', $schoolId)
            ->with(['term', 'gradeLevel', 'subject']);

        $query = $this->examAccess->scopeVisibleExams($query, $user);

        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->has('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        $exams = $query->withCount('examResults')
            ->orderBy('exam_date', 'desc')
            ->get();

        return response()->json([
            'data' => $exams,
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

        if (! $this->examAccess->canManageExams($user) && ! $this->examAccess->canEnterResults($user, $exam)) {
            abort(403, 'You can only view exams for subjects you are assigned to teach.');
        }

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
    public function store(Request $request)
    {
        $user = $request->user();
        $this->examAccess->assertCanManageExams($user);
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'term_id' => 'required|exists:terms,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'total_marks' => 'required|numeric|min:0',
            'passing_marks' => 'nullable|numeric|min:0',
            'academic_year' => 'required|string|max:9',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate term belongs to school
        $term = Term::where('school_id', $schoolId)
            ->findOrFail($request->term_id);

        // Validate grade level belongs to school
        $gradeLevel = GradeLevel::where('school_id', $schoolId)
            ->findOrFail($request->grade_level_id);

        // Validate subject belongs to school
        $subject = Subject::where('school_id', $schoolId)
            ->findOrFail($request->subject_id);

        app(\App\Services\Domain\SchoolDomainRules::class)->assertSubjectAvailableForGrade(
            (int) $schoolId,
            (int) $request->grade_level_id,
            (int) $request->subject_id,
        );

        $exam = Exam::create([
            'school_id' => $schoolId,
            'term_id' => $request->term_id,
            'grade_level_id' => $request->grade_level_id,
            'subject_id' => $request->subject_id,
            'name' => $request->name,
            'description' => $request->description,
            'exam_date' => $request->exam_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_marks' => $request->total_marks,
            'passing_marks' => $request->passing_marks,
            'academic_year' => $request->academic_year,
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
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $this->examAccess->assertCanManageExams($user);
        $schoolId = $user->school_id;

        $exam = Exam::where('school_id', $schoolId)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'term_id' => 'sometimes|exists:terms,id',
            'grade_level_id' => 'sometimes|exists:grade_levels,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'exam_date' => 'sometimes|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'total_marks' => 'sometimes|numeric|min:0',
            'passing_marks' => 'nullable|numeric|min:0',
            'academic_year' => 'sometimes|string|max:9',
            'is_published' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate relationships if provided
        if ($request->has('term_id')) {
            Term::where('school_id', $schoolId)
                ->findOrFail($request->term_id);
        }

        if ($request->has('grade_level_id')) {
            GradeLevel::where('school_id', $schoolId)
                ->findOrFail($request->grade_level_id);
        }

        if ($request->has('subject_id')) {
            Subject::where('school_id', $schoolId)
                ->findOrFail($request->subject_id);
        }

        $exam->update($request->only([
            'term_id',
            'grade_level_id',
            'subject_id',
            'name',
            'description',
            'exam_date',
            'start_time',
            'end_time',
            'total_marks',
            'passing_marks',
            'academic_year',
            'is_published',
        ]));

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
        $this->examAccess->assertCanManageExams($user);
        $schoolId = $user->school_id;

        $exam = Exam::where('school_id', $schoolId)
            ->findOrFail($id);

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
    public function recordResults(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $exam = Exam::where('school_id', $schoolId)
            ->findOrFail($id);

        $this->examAccess->assertCanEnterResults($user, $exam);
        $this->examAccess->assertExamOpenForEntry($exam);

        $validator = Validator::make($request->all(), [
            'results' => 'required|array',
            'results.*.student_id' => 'required|exists:students,id',
            'results.*.marks_obtained' => 'required|numeric|min:0|max:'.$exam->total_marks,
            'results.*.remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($exam, $request, $schoolId, $user) {
            foreach ($request->results as $resultData) {
                // Validate student belongs to school and grade level
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
            metadata: ['results_count' => count($request->results)],
        );

        return response()->json([
            'message' => 'Exam results recorded successfully',
            'data' => $exam->fresh()->load('examResults.student'),
        ]);
    }

    public function approveResults(Request $request, $id)
    {
        $user = $request->user();
        $this->examAccess->assertCanManageExams($user);
        $exam = Exam::where('school_id', $user->school_id)->findOrFail($id);

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
        $this->examAccess->assertCanManageExams($user);
        $exam = Exam::where('school_id', $user->school_id)->findOrFail($id);

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
        $schoolId = $request->user()?->school_id;

        $query = ExamResult::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
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
