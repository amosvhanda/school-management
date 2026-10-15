<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\WorkflowInstanceResource;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkflowController extends Controller
{
    public function __construct(private WorkflowService $workflows) {}

    private function authorizeWorkflow(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['isStaff'],
            permissionSlugs: ['dashboard.view'],
        );
    }

    private function authorizeWorkflowHistory(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['settings.manage', 'operations.manage'],
        );
    }


    public function pending(Request $request)
    {
        $this->authorizeWorkflow($request);

        $items = $this->workflows->pendingForUser($request->user())
            ->load(['definition.steps', 'initiator:id,name,email', 'subject', 'approvals.approver']);

        return response()->json([
            'data' => WorkflowInstanceResource::collection($items),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $this->authorizeWorkflow($request);

        $instance = WorkflowInstance::query()
            ->where('school_id', $request->user()->school_id)
            ->with(['definition.steps', 'approvals.approver', 'initiator', 'subject'])
            ->findOrFail($id);

        return response()->json(['data' => new WorkflowInstanceResource($instance)]);
    }

    public function approve(Request $request, int $id)
    {
        $this->authorizeWorkflow($request);

        $instance = WorkflowInstance::where('school_id', $request->user()->school_id)->findOrFail($id);
        $updated = $this->workflows->approve($instance, $request->user(), $request->input('comments'));
        $updated->load(['definition.steps', 'approvals.approver', 'initiator', 'subject']);

        return response()->json([
            'data' => new WorkflowInstanceResource($updated),
            'message' => 'Workflow step approved',
        ]);
    }

    public function reject(Request $request, int $id)
    {
        $this->authorizeWorkflow($request);

        $validator = Validator::make($request->all(), [
            'comments' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $instance = WorkflowInstance::where('school_id', $request->user()->school_id)->findOrFail($id);
        $updated = $this->workflows->reject($instance, $request->user(), $request->input('comments'));
        $updated->load(['definition.steps', 'approvals.approver', 'initiator', 'subject']);

        return response()->json([
            'data' => new WorkflowInstanceResource($updated),
            'message' => 'Workflow rejected',
        ]);
    }

    public function history(Request $request)
    {
        $this->authorizeWorkflowHistory($request);

        $query = WorkflowInstance::query()
            ->where('school_id', $request->user()->school_id)
            ->with(['definition.steps', 'initiator:id,name,email', 'subject', 'approvals.approver'])
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('module')) {
            $query->whereHas('definition', fn ($q) => $q->where('module', $request->module));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('definition', fn ($def) => $def
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('initiator', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        $items = $query->limit(200)->get();

        return response()->json([
            'data' => WorkflowInstanceResource::collection($items),
        ]);
    }
}
