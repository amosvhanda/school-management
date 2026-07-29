<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommunicationController extends Controller
{
    private function authorizeCommunications(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['isStaff'],
            permissionSlugs: ['communications.manage'],
        );
    }

    public function index(Request $request)
    {
        $this->authorizeCommunications($request);

        $user = $request->user();
        $this->assertStaffUser($user);

        $schoolId = $user->school_id;

        $query = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student:id,full_name,student_number', 'parent:id,name,email', 'staff:id,name']);

        // Teachers only see unassigned + own threads; admins see the full school inbox.
        if ($user->role === UserRole::Teacher) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('staff_user_id')
                    ->orWhere('staff_user_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->orderByDesc('last_message_at')->get()]);
    }

    public function show(Request $request, int $id)
    {
        $this->authorizeCommunications($request);

        $user = $request->user();
        $this->assertStaffUser($user);

        $schoolId = $user->school_id;

        $thread = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student', 'parent', 'staff'])
            ->findOrFail($id);

        $this->assertTeacherCanAccessThread($user, $thread);

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
        $this->authorizeCommunications($request);

        $user = $request->user();
        $this->assertStaffUser($user);

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

        $this->assertTeacherCanAccessThread($user, $thread);

        if ($thread->staff_user_id === null) {
            $thread->update(['staff_user_id' => $user->id]);
        }

        $message = CommunicationMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'body' => $request->body,
        ]);

        $thread->update(['last_message_at' => now(), 'status' => 'open']);

        return response()->json(['data' => $message->load('sender')], 201);
    }

    private function assertStaffUser(?User $user): void
    {
        if (! $user || ! $user->role instanceof UserRole) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($user->role, [UserRole::Parent, UserRole::Student], true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function assertTeacherCanAccessThread(User $user, CommunicationThread $thread): void
    {
        if ($user->role !== UserRole::Teacher) {
            return;
        }

        if ($thread->staff_user_id !== null && (int) $thread->staff_user_id !== (int) $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }
}
