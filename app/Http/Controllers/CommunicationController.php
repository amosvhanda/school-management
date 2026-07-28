<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CommunicationThread;
use App\Models\Student;
use App\Models\User;
use App\Services\CommunicationMessagingService;
use App\Services\ParentAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CommunicationController extends Controller
{
    public function __construct(
        private CommunicationMessagingService $messaging,
        private ParentAccessService $parentAccess,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $query = $this->messaging->appendUnreadCount(
            $this->messaging->staffInboxQuery($user),
            (int) $user->id,
        );

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%"));
            });
        }

        $query->orderByDesc('last_message_at');

        if ($request->boolean('all')) {
            return response()->json(['data' => $query->get()]);
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $count = $this->messaging->staffInboxQuery($user)
            ->whereHas('messages', function ($q) use ($user) {
                $q->whereNull('read_at')->where('sender_id', '!=', $user->id);
            })
            ->count();

        return response()->json(['data' => ['unread_threads' => $count]]);
    }

    /**
     * Parent accounts staff can message (school-scoped, staff-safe).
     */
    public function parents(Request $request)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $schoolId = $user->school_id;

        $parents = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('role', UserRole::Parent->value)
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email']);

        return response()->json(['data' => $parents]);
    }

    public function parentStudents(Request $request, int $parentUserId)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $parent = User::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->where('role', UserRole::Parent->value)
            ->findOrFail($parentUserId);

        $ids = $this->parentAccess->accessibleStudentIds($parent);
        $students = Student::query()
            ->whereIn('id', $ids)
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'student_number', 'class_id']);

        return response()->json(['data' => $students]);
    }

    public function staff(Request $request)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $staff = User::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->whereNotIn('role', [UserRole::Parent->value, UserRole::Student->value])
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email', 'role']);

        return response()->json(['data' => $staff]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'parent_user_id' => 'required|integer',
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists('students', 'id')->where(fn ($q) => $q->when(
                    $schoolId,
                    fn ($inner) => $inner->where('school_id', $schoolId),
                )),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $parent = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('role', UserRole::Parent->value)
            ->find($request->parent_user_id);

        if (! $parent) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['parent_user_id' => ['The selected parent is invalid.']],
            ], 422);
        }

        $studentId = $request->filled('student_id') ? (int) $request->student_id : null;
        if ($studentId) {
            $linked = $this->parentAccess->accessibleStudentIds($parent);
            if (! $linked->contains($studentId)) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['student_id' => ['Selected student is not linked to this parent.']],
                ], 422);
            }
        }

        $thread = CommunicationThread::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'parent_user_id' => $parent->id,
            'staff_user_id' => $user->id,
            'subject' => $request->subject,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->messaging->createMessage($thread, $user, (string) $request->message);

        return response()->json([
            'data' => $thread->fresh()->load(['student:id,full_name,student_number', 'parent:id,name,email', 'staff:id,name']),
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $schoolId = $user->school_id;

        $thread = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student', 'parent', 'staff'])
            ->findOrFail($id);

        $this->messaging->assertTeacherCanAccessThread($user, $thread);
        $this->messaging->markThreadReadForViewer($thread, (int) $user->id);

        $messages = $thread->messages()
            ->with('sender:id,name,first_name,last_name,role')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => [
                'thread' => $thread,
                'messages' => $messages,
            ],
        ]);
    }

    public function reply(Request $request, int $id)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $schoolId = $user->school_id;

        $thread = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $this->messaging->assertTeacherCanAccessThread($user, $thread);

        if ($thread->staff_user_id === null) {
            $thread->update(['staff_user_id' => $user->id]);
        }

        $message = $this->messaging->createMessage($thread, $user, (string) $request->body);

        return response()->json(['data' => $message->load('sender')], 201);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $this->authorizeCommunications($request);
        $this->messaging->authorizeStaff($user);

        $schoolId = $user->school_id;

        $thread = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $this->messaging->assertTeacherCanAccessThread($user, $thread);

        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', 'string', Rule::in(['open', 'closed'])],
            'staff_user_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->when(
                    $schoolId,
                    fn ($inner) => $inner->where('school_id', $schoolId),
                )),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('staff_user_id')) {
            if ($user->role === UserRole::Teacher && (int) $request->staff_user_id !== (int) $user->id && $request->staff_user_id !== null) {
                abort(403, 'Teachers can only claim threads for themselves.');
            }

            if ($request->filled('staff_user_id')) {
                $assignee = User::query()
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->find($request->staff_user_id);
                if (! $assignee || in_array($assignee->role, [UserRole::Parent, UserRole::Student], true)) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => ['staff_user_id' => ['Selected staff member is invalid.']],
                    ], 422);
                }
            }

            $thread->staff_user_id = $request->input('staff_user_id');
        }

        if ($request->filled('status')) {
            $thread->status = $request->string('status')->toString();
        }

        $thread->save();

        return response()->json([
            'data' => $thread->fresh()->load(['student:id,full_name,student_number', 'parent:id,name,email', 'staff:id,name']),
        ]);
    }

    private function authorizeCommunications(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['isStaff'],
            permissionSlugs: ['communications.manage'],
        );
    }
}
