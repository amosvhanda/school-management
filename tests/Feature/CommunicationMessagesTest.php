<?php

namespace Tests\Feature;

use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\TeacherNotification;
use App\Models\User;
use Tests\TestCase;

class CommunicationMessagesTest extends TestCase
{
    public function test_staff_inbox_marks_parent_messages_read_and_supports_close_assign(): void
    {
        $auth = $this->createAuthenticatedUser();
        $parent = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'parent',
        ]);

        $thread = CommunicationThread::create([
            'school_id' => $auth['school']->id,
            'parent_user_id' => $parent->id,
            'staff_user_id' => null,
            'subject' => 'Fees question',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $message = CommunicationMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $parent->id,
            'body' => 'When is the next invoice due?',
        ]);

        $list = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/communications/threads?status=open');

        $list->assertOk()
            ->assertJsonPath('data.0.id', $thread->id)
            ->assertJsonPath('data.0.unread_count', 1);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/communications/threads/'.$thread->id)
            ->assertOk();

        $this->assertNotNull($message->fresh()->read_at);

        $assignee = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'teacher',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->patchJson('/api/v1/communications/threads/'.$thread->id, [
            'staff_user_id' => $assignee->id,
            'status' => 'closed',
        ])->assertOk()
            ->assertJsonPath('data.staff_user_id', $assignee->id)
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_staff_reply_notifies_parent(): void
    {
        $auth = $this->createAuthenticatedUser();
        $parent = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'parent',
        ]);
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $thread = CommunicationThread::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'parent_user_id' => $parent->id,
            'staff_user_id' => $auth['user']->id,
            'subject' => 'Homework',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/communications/threads/'.$thread->id.'/messages', [
            'body' => 'Please submit by Friday.',
        ])->assertCreated();

        $this->assertDatabaseHas('parent_notifications', [
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'type' => 'message',
        ]);
        $this->assertSame(1, ParentNotification::where('parent_user_id', $parent->id)->count());
    }

    public function test_parent_message_notifies_admin_staff(): void
    {
        $auth = $this->createAuthenticatedUser();
        $parent = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'parent',
            'password' => bcrypt('password'),
        ]);
        $parentToken = $parent->createToken('test')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer '.$parentToken,
        ])->postJson('/api/v1/parent/portal/communications/threads', [
            'subject' => 'Transport query',
            'message' => 'Is the bus running tomorrow?',
        ])->assertCreated();

        $this->assertDatabaseHas('teacher_notifications', [
            'user_id' => $auth['user']->id,
            'type' => 'message',
        ]);
        $this->assertGreaterThanOrEqual(1, TeacherNotification::where('type', 'message')->count());
    }

    public function test_parent_can_close_and_reopen_own_thread(): void
    {
        $auth = $this->createAuthenticatedUser();
        $parent = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'parent',
            'password' => bcrypt('password'),
        ]);
        $parentToken = $parent->createToken('test')->plainTextToken;

        $thread = CommunicationThread::create([
            'school_id' => $auth['school']->id,
            'parent_user_id' => $parent->id,
            'subject' => 'Close me',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$parentToken,
        ])->patchJson('/api/v1/parent/portal/communications/threads/'.$thread->id, [
            'status' => 'closed',
        ])->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$parentToken,
        ])->patchJson('/api/v1/parent/portal/communications/threads/'.$thread->id, [
            'status' => 'open',
        ])->assertOk()
            ->assertJsonPath('data.status', 'open');
    }
}
