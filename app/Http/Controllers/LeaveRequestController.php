<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Teacher;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $query = LeaveRequest::query()
            ->with(['teacher:id,name,employee_id,department', 'requester:id,name', 'reviewer:id,name'])
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

        $leaveRequests = $query->latest('created_at')->get()->map(fn (LeaveRequest $leave) => [
            'id' => $leave->id,
            'teacher_id' => $leave->teacher_id,
            'teacher_name' => $leave->teacher?->name,
            'employee_id' => $leave->teacher?->employee_id,
            'department' => $leave->teacher?->department,
            'type' => $leave->type,
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
        ]);

        return response()->json(['data' => $leaveRequests]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'type' => 'required|string|in:annual,sick,maternity,unpaid,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $schoolId = $request->user()->school_id;
        $teacher = Teacher::query()->where('school_id', $schoolId)->findOrFail($request->teacher_id);

        $start = $request->date('start_date');
        $end = $request->date('end_date');
        $days = $start->diffInDays($end) + 1;

        $leave = LeaveRequest::create([
            'school_id' => $schoolId,
            'teacher_id' => $teacher->id,
            'requested_by' => $request->user()->id,
            'type' => $request->type,
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
        return $this->review($request, (int) $id, 'approved');
    }

    public function reject(Request $request, $id)
    {
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

        if ($status === 'approved' && $leave->teacher) {
            $today = now()->startOfDay();
            if ($leave->start_date <= $today && $leave->end_date >= $today) {
                $leave->teacher->update(['status' => 'on_leave']);
            }
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
