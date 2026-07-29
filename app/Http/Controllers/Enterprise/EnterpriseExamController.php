<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\CbtExamSession;
use App\Models\QuestionBankItem;
use App\Models\RemarkRequest;
use App\Models\Student;
use App\Models\WorkflowDelegation;
use App\Services\Enterprise\ExaminationEnterpriseService;
use App\Services\Enterprise\WorkflowEnterpriseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnterpriseExamController extends Controller
{
    public function __construct(
        private ExaminationEnterpriseService $exams,
        private WorkflowEnterpriseService $workflows,
    ) {}

    private function authorizeEnterpriseExamManage(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageExaminations'],
            permissionSlugs: ['exams.manage'],
        );
    }

    private function authorizeEnterpriseExamEntry(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageExaminations', 'canEnterExamResults', 'isStaff'],
            permissionSlugs: ['exams.manage', 'exams.enter_results'],
        );
    }


    public function questions(Request $request)
    {
        $this->authorizeEnterpriseExamManage($request);

        return response()->json(['data' => QuestionBankItem::where('school_id', $request->user()->school_id)->limit(100)->get()]);
    }

    public function storeQuestion(Request $request)
    {
        $this->authorizeEnterpriseExamManage($request);

        $data = Validator::make($request->all(), [
            'subject_id' => 'nullable|exists:subjects,id',
            'question_text' => 'required|string',
            'question_type' => 'nullable|string',
            'options' => 'nullable|array',
            'correct_answer' => 'nullable|string',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'tags' => 'nullable|array',
            'marks' => 'nullable|integer',
        ])->validate();

        return response()->json(['data' => $this->exams->addQuestion($request->user()->school_id, $data)], 201);
    }

    public function generatePaper(Request $request)
    {
        $this->authorizeEnterpriseExamManage($request);

        $data = Validator::make($request->all(), [
            'subject_id' => 'required|exists:subjects,id',
            'count' => 'required|integer|min:1|max:50',
            'difficulty' => 'nullable|string',
        ])->validate();

        return response()->json([
            'data' => $this->exams->generateRandomPaper(
                $request->user()->school_id,
                $data['subject_id'],
                $data['count'],
                ['difficulty' => $data['difficulty'] ?? null],
            ),
        ]);
    }

    public function startCbt(Request $request)
    {
        $this->authorizeEnterpriseExamEntry($request);

        $data = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'question_ids' => 'required|array|min:1',
            'exam_id' => 'nullable|exists:exams,id',
        ])->validate();

        Student::where('school_id', $request->user()->school_id)->findOrFail($data['student_id']);

        return response()->json([
            'data' => $this->exams->startCbtSession(
                $request->user()->school_id,
                $data['student_id'],
                $data['question_ids'],
                $data['exam_id'] ?? null,
            ),
        ], 201);
    }

    public function submitCbt(Request $request, int $id)
    {
        $this->authorizeEnterpriseExamEntry($request);

        $session = CbtExamSession::where('school_id', $request->user()->school_id)->findOrFail($id);
        $data = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.answer' => 'required|string',
        ])->validate();

        return response()->json(['data' => $this->exams->submitCbtSession($session, $data['answers'])]);
    }

    public function antiCheatLog(Request $request, int $sessionId)
    {
        $this->authorizeEnterpriseExamEntry($request);

        $data = Validator::make($request->all(), ['event_type' => 'required|string', 'metadata' => 'nullable|array'])->validate();
        $this->exams->logAntiCheat($sessionId, $data['event_type'], $data['metadata'] ?? null);

        return response()->json(['message' => 'Anti-cheat event logged']);
    }

    public function remarkRequests(Request $request)
    {
        $this->authorizeEnterpriseExamManage($request);

        return response()->json(['data' => RemarkRequest::where('school_id', $request->user()->school_id)->limit(100)->get()]);
    }

    public function requestRemark(Request $request)
    {
        $this->authorizeEnterpriseExamEntry($request);

        $data = Validator::make($request->all(), [
            'exam_result_id' => 'required|exists:exam_results,id',
            'student_id' => 'required|exists:students,id',
            'reason' => 'required|string',
        ])->validate();

        return response()->json(['data' => $this->exams->requestRemark($request->user()->school_id, $data)], 201);
    }

    public function delegations(Request $request)
    {
        $this->authorizeEnterpriseExamManage($request);

        return response()->json(['data' => WorkflowDelegation::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeDelegation(Request $request)
    {
        $this->authorizeEnterpriseExamManage($request);

        $data = Validator::make($request->all(), [
            'delegator_id' => 'required|exists:users,id',
            'delegate_id' => 'required|exists:users,id',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date',
            'scope' => 'nullable|string',
        ])->validate();

        return response()->json(['data' => $this->workflows->delegate($request->user()->school_id, $data)], 201);
    }
}
