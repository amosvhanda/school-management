<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $query = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student:id,full_name,student_number', 'parent:id,name,email', 'staff:id,name']);

        if (in_array($user->role, [UserRole::Teacher, UserRole::Admin, UserRole::SchoolAdmin], true)) {
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
        $user = $request->user();
        $schoolId = $user->school_id;

        $thread = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student', 'parent', 'staff'])
            ->findOrFail($id);

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
        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $schoolId = $user->school_id;

        $thread = CommunicationThread::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

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
}
