<?php

namespace Tests\Feature\Api\V1;

use App\Ai\Agents\SchoolAssistant;
use App\Models\Student;
use Laravel\Ai\Models\Conversation;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    public function test_chat_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/assistant/chat', [
            'message' => 'How many students do we have?',
        ]);

        $response->assertUnauthorized();
    }

    public function test_chat_returns_assistant_reply(): void
    {
        SchoolAssistant::fake([
            'You have 42 active students enrolled this term.',
        ]);

        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/assistant/chat', [
            'message' => 'How many students do we have?',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.reply', 'You have 42 active students enrolled this term.')
            ->assertJsonStructure([
                'message',
                'data' => ['reply', 'conversation_id'],
            ]);

        SchoolAssistant::assertPrompted('How many students do we have?');
    }

    public function test_chat_continues_existing_conversation(): void
    {
        SchoolAssistant::fake([
            'First reply',
            'Follow-up reply',
        ]);

        $auth = $this->createAuthenticatedUser();

        $conversation = Conversation::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $auth['user']->id,
            'title' => 'Enrollment question',
        ]);

        $first = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/assistant/chat', [
            'message' => 'Hello',
            'conversation_id' => $conversation->id,
        ]);

        $first->assertOk()
            ->assertJsonPath('data.reply', 'First reply');

        $second = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/assistant/chat', [
            'message' => 'Tell me more',
            'conversation_id' => $conversation->id,
        ]);

        $second->assertOk()
            ->assertJsonPath('data.reply', 'Follow-up reply');
    }

    public function test_user_can_list_their_conversations(): void
    {
        $auth = $this->createAuthenticatedUser();

        Conversation::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $auth['user']->id,
            'title' => 'Fee inquiry',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/assistant/conversations');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Fee inquiry');
    }

    public function test_user_cannot_access_another_users_conversation(): void
    {
        $auth = $this->createAuthenticatedUser();
        $other = $this->createAuthenticatedUser();

        $conversation = Conversation::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $other['user']->id,
            'title' => 'Private chat',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/assistant/chat', [
            'message' => 'Hello',
            'conversation_id' => $conversation->id,
        ]);

        $response->assertNotFound();
    }

    public function test_search_students_tool_is_school_scoped(): void
    {
        $auth = $this->createAuthenticatedUser();

        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'full_name' => 'Alice Moyo',
            'student_number' => 'STU-001',
            'status' => 'active',
        ]);

        $otherSchool = \App\Models\School::factory()->create();
        Student::factory()->create([
            'school_id' => $otherSchool->id,
            'full_name' => 'Alice Moyo',
            'student_number' => 'STU-999',
            'status' => 'active',
        ]);

        $tool = new \App\Ai\Tools\SearchStudentsTool($auth['school']->id);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request(['query' => 'Alice']));

        $this->assertStringContainsString('STU-001', (string) $result);
        $this->assertStringNotContainsString('STU-999', (string) $result);
    }
}
