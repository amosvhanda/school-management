<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Jobs\Middleware\SetTenantContext;
use App\Jobs\ProcessSchoolNotificationsJob;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelBed;
use App\Models\HostelRoom;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\NotificationQueue;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceNotificationService;
use InvalidArgumentException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_user_cannot_view_student_from_another_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        $userA = User::factory()->create([
            'role' => 'admin',
            'school_id' => $schoolA->id,
            'password' => bcrypt('password'),
        ]);

        $studentB = Student::factory()->create(['school_id' => $schoolB->id]);

        $token = $userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/v1/students/{$studentB->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_view_student_from_own_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$student->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $student->id);
    }

    public function test_school_isolation_middleware_blocks_cross_school_id_in_request(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        $userA = User::factory()->create([
            'role' => 'admin',
            'school_id' => $schoolA->id,
            'password' => bcrypt('password'),
        ]);

        $token = $userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/students', [
            'firstName' => 'John',
            'surname' => 'Doe',
            'class' => 'Form 1A',
            'school_id' => $schoolB->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_students_list_only_returns_own_school_data(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();

        Student::factory()->count(2)->create(['school_id' => $auth['school']->id]);
        Student::factory()->count(3)->create(['school_id' => $otherSchool->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/students');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_nested_school_id_in_request_body_is_blocked(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $userA = User::factory()->create([
            'role' => 'admin',
            'school_id' => $schoolA->id,
        ]);
        $token = $userA->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/students', [
                'firstName' => 'John',
                'surname' => 'Doe',
                'class' => 'Form 1A',
                'guardian' => ['school_id' => $schoolB->id],
            ])
            ->assertStatus(403);
    }

    public function test_super_admin_cannot_list_or_view_school_students(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $super = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);
        $token = $super->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/students')
            ->assertForbidden();

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/students/'.$student->id)
            ->assertNotFound();

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/admin/schools')
            ->assertOk();
    }

    public function test_users_list_does_not_include_other_schools(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        User::factory()->create([
            'role' => 'teacher',
            'school_id' => $otherSchool->id,
            'email' => 'other-school-teacher@example.com',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/users')
            ->assertOk();

        $emails = collect($response->json('data'))->pluck('email');
        $this->assertFalse($emails->contains('other-school-teacher@example.com'));
    }

    public function test_library_loans_and_messages_are_isolated_by_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();

        $ownBook = LibraryBook::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'Own Book',
            'total_copies' => 2,
            'available_copies' => 1,
        ]);
        $otherBook = LibraryBook::query()->withoutSchoolScope()->create([
            'school_id' => $otherSchool->id,
            'title' => 'Other Book',
            'total_copies' => 2,
            'available_copies' => 1,
        ]);
        $ownStudent = Student::factory()->create(['school_id' => $auth['school']->id]);
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);

        $ownLoan = LibraryLoan::query()->create([
            'book_id' => $ownBook->id,
            'student_id' => $ownStudent->id,
            'borrowed_at' => now()->toDateString(),
            'due_at' => now()->addWeek()->toDateString(),
            'status' => 'borrowed',
        ]);
        $otherLoan = LibraryLoan::query()->create([
            'book_id' => $otherBook->id,
            'student_id' => $otherStudent->id,
            'borrowed_at' => now()->toDateString(),
            'due_at' => now()->addWeek()->toDateString(),
            'status' => 'borrowed',
        ]);

        $ownThread = CommunicationThread::query()->create([
            'school_id' => $auth['school']->id,
            'parent_user_id' => $auth['user']->id,
            'subject' => 'Own thread',
            'status' => 'open',
        ]);
        $otherParent = User::factory()->create([
            'role' => 'parent',
            'school_id' => $otherSchool->id,
        ]);
        $otherThread = CommunicationThread::query()->withoutSchoolScope()->create([
            'school_id' => $otherSchool->id,
            'parent_user_id' => $otherParent->id,
            'subject' => 'Other thread',
            'status' => 'open',
        ]);
        CommunicationMessage::query()->create([
            'thread_id' => $ownThread->id,
            'sender_id' => $auth['user']->id,
            'body' => 'hello own',
        ]);
        $otherMessage = CommunicationMessage::query()->create([
            'thread_id' => $otherThread->id,
            'sender_id' => $otherParent->id,
            'body' => 'hello other',
        ]);

        $this->actingAs($auth['user']);

        $this->assertTrue(LibraryLoan::query()->whereKey($ownLoan->id)->exists());
        $this->assertFalse(LibraryLoan::query()->whereKey($otherLoan->id)->exists());
        $this->assertFalse(CommunicationMessage::query()->whereKey($otherMessage->id)->exists());

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/library/loans')
            ->assertOk();

        $loanIds = collect($this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/library/loans')
            ->json('data'))->pluck('id');
        $this->assertTrue($loanIds->contains($ownLoan->id));
        $this->assertFalse($loanIds->contains($otherLoan->id));

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/library/loans/'.$otherLoan->id.'/return')
            ->assertNotFound();
    }

    public function test_tenant_job_without_school_id_fails_closed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $job = new ProcessSchoolNotificationsJob(null);
        (new SetTenantContext($job->schoolId))->handle($job, fn () => true);
    }

    public function test_notification_job_does_not_process_another_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $studentA = Student::factory()->create(['school_id' => $schoolA->id]);
        $studentB = Student::factory()->create(['school_id' => $schoolB->id]);

        $own = NotificationQueue::query()->create([
            'school_id' => $schoolA->id,
            'type' => 'test',
            'notifiable_type' => Student::class,
            'notifiable_id' => $studentA->id,
            'channel' => 'whatsapp',
            'recipient_phone' => '+263770000001',
            'message' => 'A',
            'status' => 'pending',
        ]);
        $other = NotificationQueue::query()->create([
            'school_id' => $schoolB->id,
            'type' => 'test',
            'notifiable_type' => Student::class,
            'notifiable_id' => $studentB->id,
            'channel' => 'whatsapp',
            'recipient_phone' => '+263770000002',
            'message' => 'B',
            'status' => 'pending',
        ]);

        $job = new ProcessSchoolNotificationsJob($schoolA->id, 50);
        (new SetTenantContext($job->schoolId))->handle($job, function () use ($job) {
            $job->handle(app(AttendanceNotificationService::class));
        });

        $this->assertSame('sent', $own->fresh()->status);
        $this->assertSame('pending', $other->fresh()->status);
    }

    public function test_child_models_without_school_id_are_isolated_via_relation(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();

        $ownHostel = Hostel::query()->create([
            'school_id' => $auth['school']->id,
            'name' => 'Own Hostel',
            'status' => 'active',
        ]);
        $otherHostel = Hostel::query()->withoutSchoolScope()->create([
            'school_id' => $otherSchool->id,
            'name' => 'Other Hostel',
            'status' => 'active',
        ]);

        $ownRoom = HostelRoom::query()->create([
            'hostel_id' => $ownHostel->id,
            'room_number' => 'A1',
            'capacity' => 2,
        ]);
        $otherRoom = HostelRoom::query()->withoutGlobalScopes()->create([
            'hostel_id' => $otherHostel->id,
            'room_number' => 'B1',
            'capacity' => 2,
        ]);

        $ownBed = HostelBed::query()->create([
            'room_id' => $ownRoom->id,
            'bed_number' => '1',
            'status' => 'available',
        ]);
        $otherBed = HostelBed::query()->withoutGlobalScopes()->create([
            'room_id' => $otherRoom->id,
            'bed_number' => '1',
            'status' => 'available',
        ]);

        $ownStudent = Student::factory()->create(['school_id' => $auth['school']->id]);
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);

        $ownAllocation = HostelAllocation::query()->create([
            'student_id' => $ownStudent->id,
            'bed_id' => $ownBed->id,
            'status' => 'active',
            'allocated_at' => now()->toDateString(),
        ]);
        $otherAllocation = HostelAllocation::query()->withoutGlobalScopes()->create([
            'student_id' => $otherStudent->id,
            'bed_id' => $otherBed->id,
            'status' => 'active',
            'allocated_at' => now()->toDateString(),
        ]);

        $ownAsset = Asset::query()->create([
            'school_id' => $auth['school']->id,
            'asset_tag' => 'OWN-1',
            'name' => 'Desk',
            'category' => 'furniture',
            'status' => 'active',
        ]);
        $otherAsset = Asset::query()->withoutSchoolScope()->create([
            'school_id' => $otherSchool->id,
            'asset_tag' => 'OTH-1',
            'name' => 'Chair',
            'category' => 'furniture',
            'status' => 'active',
        ]);

        $ownDisposal = AssetDisposal::query()->create([
            'asset_id' => $ownAsset->id,
            'disposed_at' => now()->toDateString(),
            'reason' => 'worn',
        ]);
        $otherDisposal = AssetDisposal::query()->withoutGlobalScopes()->create([
            'asset_id' => $otherAsset->id,
            'disposed_at' => now()->toDateString(),
            'reason' => 'broken',
        ]);

        $this->actingAs($auth['user']);

        $this->assertTrue(HostelBed::query()->whereKey($ownBed->id)->exists());
        $this->assertFalse(HostelBed::query()->whereKey($otherBed->id)->exists());
        $this->assertTrue(HostelAllocation::query()->whereKey($ownAllocation->id)->exists());
        $this->assertFalse(HostelAllocation::query()->whereKey($otherAllocation->id)->exists());
        $this->assertTrue(AssetDisposal::query()->whereKey($ownDisposal->id)->exists());
        $this->assertFalse(AssetDisposal::query()->whereKey($otherDisposal->id)->exists());
    }
}
