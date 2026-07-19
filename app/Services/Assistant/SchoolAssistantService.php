<?php

namespace App\Services\Assistant;

use App\Ai\Agents\SchoolAssistant;
use App\Ai\Tools\GetSchoolStatsTool;
use App\Ai\Tools\SearchStudentsTool;
use App\Ai\Tools\SearchTeachersTool;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Tools\Request as ToolRequest;
use Throwable;

class SchoolAssistantService
{
    public function isProviderConfigured(): bool
    {
        $provider = (string) config('ai.default', 'openai');
        $key = config("ai.providers.{$provider}.key");

        return filled($key);
    }

    /**
     * @return array{configured: bool, provider: string, mode: string, message: string}
     */
    public function status(): array
    {
        $configured = $this->isProviderConfigured();
        $provider = (string) config('ai.default', 'openai');

        return [
            'configured' => $configured,
            'provider' => $provider,
            'mode' => $configured ? 'ai' : 'local',
            'message' => $configured
                ? 'AI provider connected. The assistant can reason over school tools.'
                : 'No AI API key configured. Running in local school-data mode (stats and search still work).',
        ];
    }

    /**
     * @return array{reply: string, conversation_id: string, mode: string}
     */
    public function chat(User $user, string $message, ?string $conversationId = null): array
    {
        if (! $user->school_id) {
            abort(403, 'School context is required to use the assistant.');
        }

        $user->loadMissing('school');

        if ($this->isProviderConfigured()) {
            try {
                return $this->chatWithAi($user, $message, $conversationId);
            } catch (Throwable $e) {
                report($e);

                // Keep the product usable even if the provider fails mid-session.
                $fallback = $this->chatLocally($user, $message, $conversationId);

                return [
                    ...$fallback,
                    'mode' => 'local_fallback',
                    'reply' => "AI provider error — answering from school data instead.\n\n".$fallback['reply'],
                ];
            }
        }

        return $this->chatLocally($user, $message, $conversationId);
    }

    /**
     * @return array{reply: string, conversation_id: string, mode: string}
     */
    private function chatWithAi(User $user, string $message, ?string $conversationId): array
    {
        $agent = SchoolAssistant::make(user: $user);

        if ($conversationId) {
            $this->assertOwnsConversation($user, $conversationId);
            $agent->continue($conversationId, $user);
        } else {
            $agent->forUser($user);
        }

        $response = $agent->prompt($message);

        return [
            'reply' => trim((string) $response->text) ?: 'I could not generate a reply. Please try again.',
            'conversation_id' => (string) $response->conversationId,
            'mode' => 'ai',
        ];
    }

    /**
     * @return array{reply: string, conversation_id: string, mode: string}
     */
    private function chatLocally(User $user, string $message, ?string $conversationId): array
    {
        $conversation = $this->resolveConversation($user, $conversationId, $message);
        $reply = $this->buildLocalReply($user, $message);

        $this->storeMessage($conversation, $user, 'user', $message);
        $this->storeMessage($conversation, $user, 'assistant', $reply);
        $conversation->touch();

        return [
            'reply' => $reply,
            'conversation_id' => (string) $conversation->id,
            'mode' => 'local',
        ];
    }

    private function buildLocalReply(User $user, string $message): string
    {
        $schoolId = (int) $user->school_id;
        $normalized = Str::lower($message);

        if ($this->mentions($normalized, ['stat', 'overview', 'summary', 'how many', 'enrollment', 'attendance', 'outstanding', 'fees', 'dashboard'])) {
            $stats = (string) (new GetSchoolStatsTool($schoolId))->handle(new ToolRequest([]));

            return "Here is the current school snapshot:\n\n{$stats}\n\nAsk me to find a student or teacher by name if you need details.";
        }

        if ($this->mentions($normalized, ['teacher', 'staff', 'educator'])) {
            $query = $this->extractSearchQuery($message, ['teacher', 'teachers', 'staff', 'find', 'search', 'who is', 'about']);
            if ($query !== '') {
                $result = (string) (new SearchTeachersTool($schoolId))->handle(new ToolRequest([
                    'query' => $query,
                ]));

                return "Teacher search for \"{$query}\":\n\n{$result}";
            }

            return 'Tell me a teacher name to search, for example: "Find teacher Moyo".';
        }

        if ($this->mentions($normalized, ['student', 'learner', 'pupil', 'find', 'search', 'who is', 'balance'])) {
            $query = $this->extractSearchQuery($message, [
                'student', 'students', 'learner', 'learners', 'pupil', 'pupils',
                'find', 'search', 'who is', 'about', 'show me', 'look up',
            ]);

            if ($query !== '') {
                $result = (string) (new SearchStudentsTool($schoolId))->handle(new ToolRequest([
                    'query' => $query,
                ]));

                return "Student search for \"{$query}\":\n\n{$result}";
            }
        }

        $schoolName = $user->school?->name ?? 'your school';

        return <<<TEXT
I can help with {$schoolName} operations right now.

Try one of these:
• "Show school stats"
• "Find student Alice"
• "Search teacher Moyo"
• "How many students are enrolled?"

Tip: set OPENAI_API_KEY in the API .env to enable full AI answers. Until then I answer from live school data tools.
TEXT;
    }

    private function resolveConversation(User $user, ?string $conversationId, string $message): Conversation
    {
        if ($conversationId) {
            $this->assertOwnsConversation($user, $conversationId);

            return Conversation::query()
                ->where('id', $conversationId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        }

        return Conversation::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => Str::limit(trim($message), 60, '…'),
        ]);
    }

    private function storeMessage(Conversation $conversation, User $user, string $role, string $content): void
    {
        ConversationMessage::query()->create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'agent' => SchoolAssistant::class,
            'role' => $role,
            'content' => $content,
            'attachments' => [],
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
            'meta' => ['source' => 'local_assistant'],
        ]);
    }

    private function assertOwnsConversation(User $user, string $conversationId): void
    {
        $exists = Conversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $exists) {
            abort(404, 'Conversation not found.');
        }
    }

    /**
     * @param  list<string>  $needles
     */
    private function mentions(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $stopWords
     */
    private function extractSearchQuery(string $message, array $stopWords): string
    {
        $query = Str::of($message)
            ->lower()
            ->replaceMatches('/[^\pL\pN\s\-]/u', ' ')
            ->replace($stopWords, ' ')
            ->squish()
            ->toString();

        return trim($query);
    }
}
