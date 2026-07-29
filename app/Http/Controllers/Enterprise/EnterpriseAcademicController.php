<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendarEntry;
use App\Models\AssessmentCategory;
use App\Models\CurriculumVersion;
use App\Models\GradebookRule;
use App\Models\PromotionRule;
use App\Models\Student;
use App\Services\Enterprise\AcademicEnterpriseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnterpriseAcademicController extends Controller
{
    public function __construct(private AcademicEnterpriseService $academic) {}

    private function authorizeEnterpriseAcademicManage(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['academics.manage'],
        );
    }

    private function authorizeEnterpriseAcademicStaff(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['isStaff', 'canEnterExamResults'],
            permissionSlugs: ['academics.manage', 'exams.enter_results', 'dashboard.view'],
        );
    }


    public function curriculum(Request $request)
    {
        $this->authorizeEnterpriseAcademicStaff($request);

        $items = CurriculumVersion::where('school_id', $request->user()->school_id)
            ->with('subject:id,name')
            ->orderByDesc('version_number')
            ->limit(100)
            ->get();

        return response()->json(['data' => $items]);
    }

    public function publishCurriculum(Request $request)
    {
        $this->authorizeEnterpriseAcademicManage($request);

        $data = Validator::make($request->all(), [
            'subject_id' => 'required|exists:subjects,id',
            'academic_year' => 'required|string',
            'syllabus' => 'nullable|string',
            'learning_outcomes' => 'array',
            'term_id' => 'nullable|exists:terms,id',
        ])->validate();

        return response()->json([
            'data' => $this->academic->publishCurriculum($request->user()->school_id, $data),
            'message' => 'Curriculum version published',
        ], 201);
    }

    public function recordOutcome(Request $request)
    {
        $this->authorizeEnterpriseAcademicStaff($request);

        $data = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'outcome_code' => 'required|string',
            'competency_level' => 'required|string',
            'score' => 'nullable|numeric',
            'assessed_on' => 'nullable|date',
        ])->validate();

        Student::where('school_id', $request->user()->school_id)->findOrFail($data['student_id']);

        return response()->json([
            'data' => $this->academic->recordOutcome($request->user()->school_id, array_merge($data, [
                'assessed_by' => $request->user()->id,
                'assessed_on' => $data['assessed_on'] ?? now()->toDateString(),
            ])),
        ], 201);
    }

    public function assessmentCategories(Request $request)
    {
        $this->authorizeEnterpriseAcademicStaff($request);

        return response()->json(['data' => AssessmentCategory::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeAssessmentCategory(Request $request)
    {
        $this->authorizeEnterpriseAcademicManage($request);

        $data = Validator::make($request->all(), [
            'name' => 'required|string',
            'type' => 'required|in:ca,project,quiz,exam',
            'weight' => 'required|numeric|min:0|max:100',
            'subject_id' => 'nullable|exists:subjects,id',
        ])->validate();

        $category = AssessmentCategory::create(array_merge($data, ['school_id' => $request->user()->school_id]));

        return response()->json(['data' => $category], 201);
    }

    public function recordAssessment(Request $request)
    {
        $this->authorizeEnterpriseAcademicStaff($request);

        $data = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'category_id' => 'required|exists:assessment_categories,id',
            'title' => 'required|string',
            'score' => 'required|numeric',
            'max_score' => 'nullable|numeric',
            'term_id' => 'nullable|exists:terms,id',
        ])->validate();

        return response()->json([
            'data' => $this->academic->recordContinuousAssessment($request->user()->school_id, array_merge($data, [
                'recorded_by' => $request->user()->id,
                'assessed_on' => now()->toDateString(),
                'max_score' => $data['max_score'] ?? 100,
            ])),
        ], 201);
    }

    public function weightedGrade(Request $request, int $studentId, int $subjectId)
    {
        $this->authorizeEnterpriseAcademicStaff($request);

        Student::where('school_id', $request->user()->school_id)->findOrFail($studentId);

        return response()->json(['data' => $this->academic->calculateWeightedGrade($studentId, $subjectId, $request->integer('term_id') ?: null)]);
    }

    public function gpaRanking(Request $request)
    {
        $this->authorizeEnterpriseAcademicManage($request);

        return response()->json([
            'data' => $this->academic->calculateGpaAndRank(
                $request->user()->school_id,
                $request->integer('grade_level_id') ?: null,
            ),
        ]);
    }

    public function storeGradebookRule(Request $request)
    {
        $this->authorizeEnterpriseAcademicManage($request);

        $data = Validator::make($request->all(), [
            'subject_id' => 'nullable|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'category_weights' => 'required|array',
            'pass_mark' => 'nullable|numeric',
        ])->validate();

        $rule = GradebookRule::create(array_merge($data, ['school_id' => $request->user()->school_id]));

        return response()->json(['data' => $rule], 201);
    }

    public function checkPrerequisites(Request $request, int $studentId, int $subjectId)
    {
        $this->authorizeEnterpriseAcademicStaff($request);

        Student::where('school_id', $request->user()->school_id)->findOrFail($studentId);

        return response()->json(['data' => $this->academic->checkPrerequisites($studentId, $subjectId)]);
    }

    public function promotionRules(Request $request)
    {
        $this->authorizeEnterpriseAcademicStaff($request);

        return response()->json(['data' => PromotionRule::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storePromotionRule(Request $request)
    {
        $this->authorizeEnterpriseAcademicManage($request);

        $data = Validator::make($request->all(), [
            'name' => 'required|string',
            'conditions' => 'required|array',
            'action' => 'required|in:promote,repeat,graduate',
        ])->validate();

        return response()->json(['data' => PromotionRule::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }

    public function evaluatePromotion(Request $request, int $studentId)
    {
        $this->authorizeEnterpriseAcademicManage($request);

        Student::where('school_id', $request->user()->school_id)->findOrFail($studentId);

        return response()->json(['data' => ['action' => $this->academic->evaluatePromotionRules($request->user()->school_id, $studentId)]]);
    }

    public function calendar(Request $request)
    {
        $this->authorizeEnterpriseAcademicStaff($request);

        return response()->json(['data' => $this->academic->calendar($request->user()->school_id, $request->get('year'))]);
    }

    public function storeCalendarEntry(Request $request)
    {
        $this->authorizeEnterpriseAcademicManage($request);

        $data = Validator::make($request->all(), [
            'entry_type' => 'required|string',
            'title' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_holiday' => 'boolean',
            'term_id' => 'nullable|exists:terms,id',
        ])->validate();

        $entry = AcademicCalendarEntry::create(array_merge($data, ['school_id' => $request->user()->school_id]));

        return response()->json(['data' => $entry], 201);
    }
}
