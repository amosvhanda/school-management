<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DisciplinaryRecord;
use App\Models\Grade;
use App\Models\InventoryItem;
use App\Models\ParentNotification;
use App\Models\School;
use App\Models\SchoolTrip;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianService;
use Tests\TestCase;

class ParentPortalApiTest extends TestCase
{
    public function test_parent_portal_dashboard_and_children(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $school->id,
        ]);
        $student = Student::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        $parent->students()->attach($student->id, [
            'school_id' => $school->id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        $token = $parent->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)
            ->getJson('/api/v1/parent/portal/children')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id);

        $this->withHeaders($headers)
            ->getJson('/api/v1/parent/portal/dashboard')
            ->assertOk()
            ->assertJsonPath('data.children_count', 1);
    }

    public function test_auth_me_includes_children_for_parent(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $school->id,
        ]);
        $student = Student::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        $parent->students()->attach($student->id, [
            'school_id' => $school->id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        $token = $parent->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonCount(1, 'data.user.children')
            ->assertJsonPath('data.user.children.0.id', $student->id);
    }

    public function test_parent_cannot_access_unlinked_student(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $school->id,
        ]);
        $otherStudent = Student::factory()->create(['school_id' => $school->id]);

        $token = $parent->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson("/api/v1/parent/portal/students/{$otherStudent->id}/fees")
            ->assertForbidden();
    }

    public function test_disciplinary_record_notifies_linked_parents(): void
    {
        $auth = $this->createAuthenticatedUser();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $auth['school']->id,
            'email' => 'linked-parent@example.com',
        ]);
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $parent->students()->attach($student->id, [
            'school_id' => $auth['school']->id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/disciplinary-records', [
                'student_id' => $student->id,
                'incident_date' => now()->toDateString(),
                'category' => 'Late arrival',
                'severity' => 'minor',
                'description' => 'Arrived 30 minutes late.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('parent_notifications', [
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'type' => 'disciplinary',
        ]);
    }

    public function test_guardian_link_syncs_parent_student_pivot(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $guardian = app(GuardianService::class)->createOrFindGuardian([
            'first_name' => 'Mary',
            'last_name' => 'Guardian',
            'email' => 'guardian-sync@example.com',
            'phone' => '0771234567',
            'relationship' => 'mother',
            'is_primary' => true,
        ], $auth['school']->id, $student->id);

        $parentUserId = $guardian->user_id;
        $this->assertNotNull($parentUserId);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parentUserId,
            'student_id' => $student->id,
        ]);
    }

    public function test_parent_can_view_discipline_and_notifications(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $school->id,
        ]);
        $student = Student::factory()->create(['school_id' => $school->id]);

        $parent->students()->attach($student->id, [
            'school_id' => $school->id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        DisciplinaryRecord::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'incident_date' => now(),
            'category' => 'Uniform',
            'severity' => 'minor',
            'description' => 'Incorrect uniform.',
            'parent_notified' => true,
        ]);

        ParentNotification::create([
            'school_id' => $school->id,
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'type' => 'disciplinary',
            'title' => 'Disciplinary notice',
            'body' => 'Test notice',
        ]);

        $token = $parent->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)
            ->getJson("/api/v1/parent/portal/students/{$student->id}/discipline")
            ->assertOk()
            ->assertJsonCount(1, 'data.records');

        $this->withHeaders($headers)
            ->getJson('/api/v1/parent/portal/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_non_parent_cannot_access_parent_portal(): void
    {
        $auth = $this->createAuthenticatedUser('admin');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/parent/portal/dashboard')
            ->assertForbidden();
    }

    public function test_parent_can_view_and_download_linked_child_results(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $school->id,
        ]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'full_name' => 'Child One',
            'student_number' => 'STU-2002',
        ]);

        $parent->students()->attach($student->id, [
            'school_id' => $school->id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        Grade::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'subject' => 'English',
            'score' => 71,
            'total' => 100,
            'grade' => 'B',
            'term' => 'Term 2',
            'year' => now()->year,
        ]);

        $token = $parent->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)
            ->getJson("/api/v1/parent/portal/students/{$student->id}/results")
            ->assertOk()
            ->assertJsonPath('data.student.id', $student->id)
            ->assertJsonCount(1, 'data.report_card_grades')
            ->assertJsonPath('data.report_card_grades.0.subject', 'English');

        $download = $this->withHeaders($headers)
            ->get("/api/v1/students/{$student->id}/results/download?format=html");

        $download->assertOk();
        $this->assertStringContainsString('Child One', $download->getContent());
        $this->assertStringContainsString('English', $download->getContent());
    }

    public function test_parent_can_buy_store_items_for_linked_child(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $school->id,
        ]);
        $student = Student::factory()->create(['school_id' => $school->id, 'balance' => 0, 'status' => 'active']);
        $parent->students()->attach($student->id, [
            'school_id' => $school->id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        $item = InventoryItem::create([
            'school_id' => $school->id,
            'name' => 'School Shirt',
            'type' => 'uniform',
            'size' => 'M',
            'unit_price' => 20,
            'currency' => 'USD',
            'stock_quantity' => 5,
            'billing_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        $token = $parent->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)
            ->getJson('/api/v1/parent/portal/store/items?type=uniform')
            ->assertOk()
            ->assertJsonPath('data.0.id', $item->id);

        $this->withHeaders($headers)
            ->postJson('/api/v1/parent/portal/store/buy', [
                'student_id' => $student->id,
                'items' => [['item_id' => $item->id, 'quantity' => 2]],
            ])
            ->assertCreated();

        $this->assertEquals(3, $item->fresh()->stock_quantity);
        $this->assertGreaterThan(0, (float) $student->fresh()->balance);
    }

    public function test_parent_can_enroll_linked_child_on_school_trip(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $school->id,
        ]);
        $student = Student::factory()->create(['school_id' => $school->id, 'balance' => 0, 'status' => 'active']);
        $parent->students()->attach($student->id, [
            'school_id' => $school->id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        $trip = SchoolTrip::create([
            'school_id' => $school->id,
            'name' => 'Great Zimbabwe Tour',
            'destination' => 'Masvingo',
            'trip_date' => now()->addWeeks(2)->toDateString(),
            'fee_amount' => 50,
            'currency' => 'USD',
            'capacity' => 30,
            'is_active' => true,
            'open_for_registration' => true,
        ]);

        $token = $parent->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)
            ->getJson('/api/v1/parent/portal/trips')
            ->assertOk()
            ->assertJsonPath('data.0.id', $trip->id);

        $this->withHeaders($headers)
            ->postJson("/api/v1/parent/portal/trips/{$trip->id}/enroll", [
                'student_id' => $student->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('school_trip_enrollments', [
            'school_trip_id' => $trip->id,
            'student_id' => $student->id,
            'status' => 'enrolled',
        ]);
        $this->assertGreaterThan(0, (float) $student->fresh()->balance);
    }
}
