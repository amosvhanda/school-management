<?php

namespace App\Http\Controllers\Api\V1;

use App\Ai\Agents\SchoolAssistant;
use App\Http\Requests\Api\V1\Assistant\ChatRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Ai\Models\Conversation;

class AssistantController extends Controller
{
    public function chat(ChatRequest $request)
    {
        $user = $request->user();

        if (! $user->school_id) {
            return $this->forbidden('School context is required to use the assistant.');
        }

        $user->loadMissing('school');

        $agent = SchoolAssistant::make(user: $user);

        if ($request->filled('conversation_id')) {
            $this->authorizeConversation($user, $request->string('conversation_id')->toString());
            $agent->continue($request->string('conversation_id')->toString(), $user);
        } else {
            $agent->forUser($user);
        }

        $response = $agent->prompt($request->string('message')->toString());

        return $this->success([
            'reply' => $response->text,
            'conversation_id' => $response->conversationId,
        ]);
    }

    public function conversations(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->latest('updated_at')
            ->limit($request->integer('limit', 20))
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return $this->success($conversations);
    }

    public function showConversation(Request $request, string $conversationId)
    {
        $user = $request->user();

        $this->authorizeConversation($user, $conversationId);

        $conversation = Conversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->with(['messages' => fn ($query) => $query->orderBy('created_at')])
            ->firstOrFail();

        return $this->success([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $conversation->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at,
            ]),
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
        ]);
    }

    private function authorizeConversation(User $user, string $conversationId): void
    {
        $exists = Conversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $exists) {
            abort(404, 'Conversation not found.');
        }
    }
}
