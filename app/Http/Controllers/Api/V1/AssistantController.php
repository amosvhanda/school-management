<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Assistant\ChatRequest;
use App\Models\User;
use App\Services\Assistant\SchoolAssistantService;
use Illuminate\Http\Request;
use Laravel\Ai\Models\Conversation;

class AssistantController extends Controller
{
    public function __construct(private SchoolAssistantService $assistant) {}

    public function status()
    {
        return $this->success($this->assistant->status());
    }

    public function chat(ChatRequest $request)
    {
        $user = $request->user();

        if (! $user->school_id) {
            return $this->forbidden('School context is required to use the assistant. Sign in with a school staff account.');
        }

        $result = $this->assistant->chat(
            $user,
            $request->string('message')->toString(),
            $request->filled('conversation_id')
                ? $request->string('conversation_id')->toString()
                : null,
        );

        return $this->success($result);
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
                'role' => (string) $message->role,
                'content' => (string) $message->content,
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
