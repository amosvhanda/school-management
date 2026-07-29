<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ResolvesPlatformSchoolScope;
use App\Models\BehaviorPoint;
use App\Models\StaffFeedComment;
use App\Models\StaffFeedPost;
use App\Models\StaffTask;
use App\Models\Student;
use App\Models\StudentIntervention;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlatformStaffController extends Controller
{
    private function authorizePlatformStaffStudents(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageStudents', 'isStaff'],
            permissionSlugs: ['students.manage', 'hr.manage'],
        );
    }

    private function authorizePlatformStaffFeed(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['isStaff'],
            permissionSlugs: ['dashboard.view', 'hr.manage'],
        );
    }

    use ResolvesPlatformSchoolScope;

    public function behaviorPoints(Request $request, ?int $studentId = null)
    {
        $this->authorizePlatformStaffStudents($request);

        $query = $this->scopeToPlatformSchool(BehaviorPoint::query(), $request)
            ->with(['student:id,full_name', 'school:id,name,code']);

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        return response()->json(['data' => $query->orderByDesc('recorded_on')->limit(100)->get()]);
    }

    public function storeBehaviorPoint(Request $request)
    {
        $this->authorizePlatformStaffStudents($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'student_id' => 'required|exists:students,id',
            'points' => 'required|integer',
            'category' => 'required|string',
            'description' => 'required|string',
            'recorded_on' => 'nullable|date',
        ])->validate();

        Student::where('school_id', $schoolId)->findOrFail($data['student_id']);

        $point = BehaviorPoint::create([
            'school_id' => $schoolId,
            'student_id' => $data['student_id'],
            'points' => $data['points'],
            'category' => $data['category'],
            'description' => $data['description'],
            'recorded_by' => $request->user()->id,
            'recorded_on' => $data['recorded_on'] ?? now()->toDateString(),
        ]);

        return response()->json(['data' => $point, 'message' => 'Behavior points recorded'], 201);
    }

    public function behaviorSummary(Request $request, int $studentId)
    {
        $this->authorizePlatformStaffStudents($request);

        $schoolId = $this->platformSchoolId($request);
        $studentQuery = Student::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $studentQuery->findOrFail($studentId);
        $total = BehaviorPoint::where('student_id', $studentId)->sum('points');

        return response()->json(['data' => ['student_id' => $studentId, 'total_points' => (int) $total]]);
    }

    public function interventions(Request $request, ?int $studentId = null)
    {
        $this->authorizePlatformStaffStudents($request);

        $query = $this->scopeToPlatformSchool(StudentIntervention::query(), $request)
            ->with(['student:id,full_name', 'school:id,name,code']);

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        return response()->json(['data' => $query->orderByDesc('start_date')->limit(100)->get()]);
    }

    public function storeIntervention(Request $request)
    {
        $this->authorizePlatformStaffStudents($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'student_id' => 'required|exists:students,id',
            'intervention_type' => 'required|string',
            'summary' => 'required|string',
            'action_plan' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'follow_up_date' => 'nullable|date',
        ])->validate();

        Student::where('school_id', $schoolId)->findOrFail($data['student_id']);

        $intervention = StudentIntervention::create([
            'school_id' => $schoolId,
            'student_id' => $data['student_id'],
            'intervention_type' => $data['intervention_type'],
            'summary' => $data['summary'],
            'action_plan' => $data['action_plan'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $request->user()->id,
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'follow_up_date' => $data['follow_up_date'] ?? null,
        ]);

        return response()->json(['data' => $intervention, 'message' => 'Intervention logged'], 201);
    }

    public function tasks(Request $request)
    {
        $this->authorizePlatformStaffFeed($request);

        $query = $this->scopeToPlatformSchool(StaffTask::query(), $request)
            ->with([
                'school:id,name,code',
                'assignee:id,name,email,role,school_id',
                'assigner:id,name,email',
            ]);

        if ($request->boolean('mine')) {
            $query->where('assigned_to', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        return response()->json([
            'data' => $query
                ->orderByRaw("CASE WHEN status IN ('completed','cancelled') THEN 1 ELSE 0 END")
                ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
                ->orderBy('due_date')
                ->limit(200)
                ->get(),
        ]);
    }

    public function storeTask(Request $request)
    {
        $this->authorizePlatformStaffFeed($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'due_date' => 'nullable|date',
        ])->validate();

        $assignee = User::query()
            ->whereKey($data['assigned_to'])
            ->where('school_id', $schoolId)
            ->whereNotIn('role', ['student', 'parent'])
            ->first();

        if (! $assignee) {
            throw ValidationException::withMessages([
                'assigned_to' => ['Select a staff member from the chosen school.'],
            ]);
        }

        $task = StaffTask::create([
            'school_id' => $schoolId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assigned_to' => $assignee->id,
            'assigned_by' => $request->user()->id,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
            'due_date' => $data['due_date'] ?? null,
        ]);

        $task->load(['school:id,name,code', 'assignee:id,name,email,role', 'assigner:id,name,email']);

        return response()->json(['data' => $task, 'message' => 'Task assigned'], 201);
    }

    public function updateTask(Request $request, int $id)
    {
        $this->authorizePlatformStaffFeed($request);

        $task = $this->scopeToPlatformSchool(StaffTask::query(), $request)->findOrFail($id);
        $data = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'due_date' => 'nullable|date',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ])->validate();

        $updates = collect($data)->filter(fn ($value) => $value !== null)->all();

        if (($updates['status'] ?? null) === 'completed') {
            $updates['completed_at'] = now();
            $updates['progress_percent'] = 100;
        } elseif (array_key_exists('status', $updates) && $updates['status'] !== 'completed') {
            $updates['completed_at'] = null;
        }

        $task->update($updates);
        $task->load(['school:id,name,code', 'assignee:id,name,email,role', 'assigner:id,name,email']);

        return response()->json(['data' => $task, 'message' => 'Task updated']);
    }

    public function feed(Request $request)
    {
        $this->authorizePlatformStaffFeed($request);

        $posts = $this->scopeToPlatformSchool(StaffFeedPost::query(), $request)
            ->with(['author:id,name', 'comments.author:id,name', 'school:id,name,code'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => $posts]);
    }

    public function postFeed(Request $request)
    {
        $this->authorizePlatformStaffFeed($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'body' => 'required|string',
            'attachments' => 'array',
            'visibility' => 'in:staff,department',
        ])->validate();

        $post = StaffFeedPost::create([
            'school_id' => $schoolId,
            'author_id' => $request->user()->id,
            'body' => $data['body'],
            'attachments' => $data['attachments'] ?? null,
            'visibility' => $data['visibility'] ?? 'staff',
        ]);

        return response()->json(['data' => $post, 'message' => 'Posted to staff feed'], 201);
    }

    public function commentFeed(Request $request, int $id)
    {
        $this->authorizePlatformStaffFeed($request);

        $post = $this->scopeToPlatformSchool(StaffFeedPost::query(), $request)->findOrFail($id);
        $data = Validator::make($request->all(), ['body' => 'required|string'])->validate();

        $comment = StaffFeedComment::create([
            'post_id' => $post->id,
            'author_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json(['data' => $comment, 'message' => 'Comment added'], 201);
    }
}
