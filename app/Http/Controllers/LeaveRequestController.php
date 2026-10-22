<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Teacher;
use App\Services\AuditService;
use App\Services\TeacherLeaveStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    private function authorizeLeaveAccess(Request $request, bool $manage = false): void
    {
        if ($manage) {
            $this->authorizeModuleAccess(
                $request,
                capabilities: ['canManageTeachers'],
                permissionSlugs: ['hr.manage'],
            );

            return;
        }

        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers', 'isStaff'],
            permissionSlugs: ['hr.manage'],
        );
    }

    public function __construct(
        private AuditService $auditService,
        private TeacherLeaveStatusService $leaveStatusService,
    ) {}

    public function index(Request $request)
    {
        $this->authorizeLeaveAccess($request, manage: false);
        $schoolId = $request->user()?->school_id;

        $query = LeaveRequest::query()
            ->with([
                'teacher:id,name,employee_id,department',
                'leaveType:id,name,code',
                'requester:id,name',
                'reviewer:id,name',
            ])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('teacher', function ($teacherQuery) use ($search) {
                        $teacherQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%");
                    });
            });
        }

        $query->latest('created_at');

        $mapLeave = fn (LeaveRequest $leave) => [
            'id' => $leave->id,
            'teacher_id' => $leave->teacher_id,
            'teacher_name' => $leave->teacher?->name,
            'employee_id' => $leave->teacher?->employee_id,
            'department' => $leave->teacher?->department,
            'type' => $leave->type,
            'leave_type_id' => $leave->leave_type_id,
            'leave_type_name' => $leave->leaveType?->name,
            'start_date' => $leave->start_date?->toDateString(),
            'end_date' => $leave->end_date?->toDateString(),
            'days' => $leave->days,
            'reason' => $leave->reason,
            'status' => $leave->status,
            'applied_date' => $leave->created_at?->toDateString(),
            'requested_by' => $leave->requested_by,
            'requested_by_name' => $leave->requester?->name,
            'reviewed_by' => $leave->reviewer?->name,
            'reviewed_at' => $leave->reviewed_at?->toDateTimeString(),
            'review_notes' => $leave->review_notes,
            'created_at' => $leave->created_at?->toIso8601String(),
            'updated_at' => $leave->updated_at?->toIso8601String(),
        ];

        if ($request->boolean('all')) {
            return response()->json([
                'data' => $query->limit(500)->get()->map($mapLeave)->values(),
            ]);
        }

        if ($request->filled('limit') && ! $request->filled('page') && ! $request->filled('per_page')) {
            $limit = min(max((int) $request->input('limit'), 1), 200);

            return response()->json([
                'data' => $query->limit($limit)->get()->map($mapLeave)->values(),
            ]);
        }

        $perPage = min(max($request->integer('per_page', 25), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => collect($paginator->items())->map($mapLeave)->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeLeaveAccess($request, manage: false);
        $schoolId = $request->user()->school_id;

        $validator = Validator::make($request->all(), [
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'leave_type_id' => [
                'nullable',
                'integer',
                Rule::exists('leave_types', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true)),
            ],
            'type' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if (! $request->filled('leave_type_id') && ! $request->filled('type')) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['leave_type_id' => ['Select a leave type.']],
            ], 422);
        }

        $teacher = Teacher::query()->where('school_id', $schoolId)->findOrFail($request->teacher_id);

        $leaveType = null;
        if ($request->filled('leave_type_id')) {
            $leaveType = LeaveType::query()
                ->where('school_id', $schoolId)
                ->findOrFail($request->leave_type_id);
        }

        $type = $leaveType?->code ?: ($leaveType?->name ?: $request->type);

        $start = $request->date('start_date');
        $end = $request->date('end_date');
        $days = $start->diffInDays($end) + 1;

        $leave = LeaveRequest::create([
            'school_id' => $schoolId,
            'teacher_id' => $teacher->id,
            'leave_type_id' => $leaveType?->id,
            'requested_by' => $request->user()->id,
            'type' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Leave request submitted',
            'data' => $leave->load(['teacher', 'requester']),
        ], 201);
    }

    public function approve(Request $request, $id)
    {
        $this->authorizeLeaveAccess($request, manage: true);
        return $this->review($request, (int) $id, 'approved');
    }

    public function reject(Request $request, $id)
    {
        $this->authorizeLeaveAccess($request, manage: true);
        return $this->review($request, (int) $id, 'rejected');
    }

    private function review(Request $request, int $id, string $status)
    {
        $schoolId = $request->user()?->school_id;

        $leave = LeaveRequest::query()
            ->with('teacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Leave request has already been reviewed'], 422);
        }

        $notes = $request->input('notes') ?? $request->input('reason');

        if ($status === 'rejected' && ! filled(trim((string) $notes))) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['notes' => ['A rejection reason is required for audit purposes.']],
            ], 422);
        }

        $previousStatus = $leave->status;

        $leave->update([
            'status' => $status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        $teacherName = $leave->teacher?->name ?? 'Staff member';

        if ($leave->teacher_id) {
            $this->leaveStatusService->syncTeacher((int) $leave->teacher_id);
        }

        $this->auditService->log(
            module: 'hr',
            action: $status,
            auditable: $leave->fresh(),
            oldValues: ['status' => $previousStatus],
            newValues: [
                'status' => $status,
                'review_notes' => $notes,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now()->toDateTimeString(),
            ],
            description: "Leave request {$status} for {$teacherName} ({$leave->type}, {$leave->days} day(s))",
            metadata: [
                'teacher_id' => $leave->teacher_id,
                'teacher_name' => $teacherName,
                'reviewed_by_name' => $request->user()->name,
            ],
        );

        return response()->json([
            'message' => "Leave request {$status} successfully",
            'data' => $leave->fresh(['teacher', 'reviewer', 'requester']),
        ]);
    }
}
