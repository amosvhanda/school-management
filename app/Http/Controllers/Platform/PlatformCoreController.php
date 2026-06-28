<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\DataMaskingRule;
use App\Models\RetentionPolicy;
use App\Models\School;
use App\Models\Student;
use App\Services\Platform\BranchHierarchyService;
use App\Services\Platform\DataMaskingService;
use App\Services\Platform\DataRetentionService;
use App\Services\Platform\PolicyEngineService;
use App\Services\Platform\RecordVersionService;
use App\Services\Platform\WorkflowBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlatformCoreController extends Controller
{
    public function __construct(
        private PolicyEngineService $policies,
        private WorkflowBuilderService $workflowBuilder,
        private BranchHierarchyService $branches,
        private DataRetentionService $retention,
        private DataMaskingService $masking,
        private RecordVersionService $versions,
    ) {}

    public function policyRules(Request $request)
    {
        return response()->json(['data' => $this->policies->listRules($request->user()->school_id)]);
    }

    public function storePolicyRule(Request $request)
    {
        $data = Validator::make($request->all(), [
            'code' => 'required|string',
            'name' => 'required|string',
            'module' => 'required|string',
            'trigger_event' => 'required|string',
            'conditions' => 'array',
            'actions' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ])->validate();

        $rule = $this->policies->upsertRule($request->user()->school_id, $data, $request->input('id'));

        return response()->json(['data' => $rule, 'message' => 'Policy rule saved'], $request->input('id') ? 200 : 201);
    }

    public function evaluatePolicy(Request $request)
    {
        $data = Validator::make($request->all(), [
            'trigger_event' => 'required|string',
            'context' => 'array',
        ])->validate();

        return response()->json([
            'data' => $this->policies->evaluate(
                $request->user()->school_id,
                $data['trigger_event'],
                $data['context'] ?? [],
            ),
        ]);
    }

    public function workflowDefinitions(Request $request)
    {
        return response()->json(['data' => $this->workflowBuilder->listDefinitions($request->user()->school_id)]);
    }

    public function saveWorkflowDefinition(Request $request)
    {
        $data = Validator::make($request->all(), [
            'code' => 'required|string',
            'name' => 'required|string',
            'module' => 'required|string',
            'description' => 'nullable|string',
            'layout' => 'nullable|array',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string',
            'steps.*.approver_role' => 'nullable|string',
            'steps.*.escalation_hours' => 'nullable|integer',
            'steps.*.position' => 'nullable|array',
            'is_active' => 'boolean',
        ])->validate();

        $definition = $this->workflowBuilder->saveDefinition(
            $request->user()->school_id,
            $data,
            $request->input('id'),
        );

        return response()->json(['data' => $definition, 'message' => 'Workflow definition saved'], 201);
    }

    public function branchTree(Request $request)
    {
        $school = School::findOrFail($request->user()->school_id);

        return response()->json(['data' => $this->branches->tree($school)]);
    }

    public function createBranch(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => 'required|string',
            'code' => 'required|string|unique:schools,code',
            'branch_type' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
        ])->validate();

        $parent = School::findOrFail($request->user()->school_id);
        $branch = $this->branches->createBranch($parent, $data);

        return response()->json(['data' => $branch, 'message' => 'Branch created'], 201);
    }

    public function retentionPolicies(Request $request)
    {
        $policies = RetentionPolicy::where('school_id', $request->user()->school_id)->get();

        return response()->json(['data' => $policies]);
    }

    public function storeRetentionPolicy(Request $request)
    {
        $data = Validator::make($request->all(), [
            'module' => 'required|string',
            'retain_years' => 'required|integer|min:1',
            'archive_action' => 'nullable|string',
            'is_active' => 'boolean',
        ])->validate();

        $policy = RetentionPolicy::updateOrCreate(
            ['school_id' => $request->user()->school_id, 'module' => $data['module']],
            [
                'retain_years' => $data['retain_years'],
                'archive_action' => $data['archive_action'] ?? 'archive',
                'is_active' => $data['is_active'] ?? true,
            ],
        );

        return response()->json(['data' => $policy, 'message' => 'Retention policy saved']);
    }

    public function maskingRules(Request $request)
    {
        return response()->json(['data' => DataMaskingRule::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeMaskingRule(Request $request)
    {
        $data = Validator::make($request->all(), [
            'module' => 'required|string',
            'field' => 'required|string',
            'visible_roles' => 'required|array',
            'mask_pattern' => 'nullable|string',
            'is_active' => 'boolean',
        ])->validate();

        $rule = DataMaskingRule::updateOrCreate(
            [
                'school_id' => $request->user()->school_id,
                'module' => $data['module'],
                'field' => $data['field'],
            ],
            [
                'visible_roles' => $data['visible_roles'],
                'mask_pattern' => $data['mask_pattern'] ?? '***',
                'is_active' => $data['is_active'] ?? true,
            ],
        );

        return response()->json(['data' => $rule]);
    }

    public function studentVersions(Request $request, int $id)
    {
        $student = Student::where('school_id', $request->user()->school_id)->findOrFail($id);

        return response()->json(['data' => $this->versions->history($student)]);
    }
}
