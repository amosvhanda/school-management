<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\GuardianResolutionService;
use App\Services\StudentResolutionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentStudentRelationshipsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_student_resolution_links_by_school_scoped_email_and_persists_user_id(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'student');
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
            'status' => 'active',
        ]);

        $resolved = app(StudentResolutionService::class)->resolveForUser($auth['user']);

        $this->assertNotNull($resolved);
        $this->assertSame($student->id, $resolved->id);
        $this->assertSame($auth['user']->id, $resolved->fresh()->user_id);
    }

    public function test_auth_me_includes_student_id_after_email_link(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'student');
        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
            'status' => 'active',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $studentId = $response->json('data.student_id')
            ?? $response->json('data.user.student_id')
            ?? $response->json('student_id');

        $this->assertNotNull($studentId, 'Expected student_id in auth/me payload');
    }

    public function test_student_cannot_view_classmate_record(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'student');
        $own = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'email' => $auth['user']->email,
            'status' => 'active',
        ]);
        $other = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/students/'.$own->id)
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/students/'.$other->id)
            ->assertForbidden();
    }

    public function test_student_portal_dashboard_requires_linked_profile(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'student');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/dashboard')
            ->assertForbidden();

        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
            'status' => 'active',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/dashboard')
            ->assertOk()
            ->assertJsonPath('data.student.full_name', fn ($v) => is_string($v) && $v !== '')
            ->assertJsonStructure([
                'data' => [
                    'student',
                    'attendance_summary',
                    'assignments',
                    'announcements',
                    'stats',
                ],
            ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/assignments')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/announcements')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/timetable')
            ->assertOk();
    }

    public function test_guardian_resolution_links_by_email_and_syncs_parent_student(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'parent');
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);
        $guardian = Guardian::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
        ]);

        DB::table('guardian_student')->insert([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => 'parent',
            'is_primary' => true,
            'can_pickup' => true,
            'emergency_contact' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resolved = app(GuardianResolutionService::class)->resolveForUser($auth['user']);

        $this->assertNotNull($resolved);
        $this->assertSame($guardian->id, $resolved->id);
        $this->assertSame($auth['user']->id, $resolved->fresh()->user_id);
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $auth['user']->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_auth_me_includes_guardian_id_and_children_after_email_link(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'parent');
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
            'full_name' => 'Demo Child',
        ]);
        $guardian = Guardian::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
        ]);
        DB::table('guardian_student')->insert([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => 'parent',
            'is_primary' => true,
            'can_pickup' => true,
            'emergency_contact' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $guardianId = $response->json('data.guardian_id')
            ?? $response->json('data.user.guardian_id')
            ?? $response->json('guardian_id');

        $this->assertNotNull($guardianId);
        $children = $response->json('data.children')
            ?? $response->json('data.user.children')
            ?? $response->json('children')
            ?? [];
        $this->assertNotEmpty($children);
    }

    public function test_parent_cannot_access_unlinked_child_in_portal(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'parent');
        $linked = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);
        $other = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);

        DB::table('parent_student')->insert([
            'parent_id' => $auth['user']->id,
            'student_id' => $linked->id,
            'school_id' => $auth['school']->id,
            'relationship' => 'parent',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/parent/portal/students/'.$linked->id.'/attendance')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/parent/portal/students/'.$other->id.'/attendance')
            ->assertForbidden();
    }

    public function test_student_portal_dashboard_returns_grade_scores(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'student');
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'email' => $auth['user']->email,
            'status' => 'active',
        ]);

        \App\Models\Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'subject' => 'Mathematics',
            'assessment_type' => 'test',
            'score' => 78.5,
            'total' => 100,
            'grade' => 'B',
            'term' => '1',
            'year' => (int) now()->year,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/dashboard')
            ->assertOk()
            ->assertJsonPath('data.recent_grades.0.subject', 'Mathematics')
            ->assertJsonPath('data.recent_grades.0.score', '78.50');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/grades')
            ->assertOk()
            ->assertJsonPath('data.grades.0.score', '78.50');
    }

    public function test_student_portal_assignments_and_announcements_are_scoped(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'student');
        $ownClass = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $otherClass = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'email' => $auth['user']->email,
            'status' => 'active',
            'class_id' => $ownClass->id,
        ]);

        $visibleAssignment = Assignment::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'Class Essay',
            'description' => 'Write about local history',
            'subject' => 'History',
            'class_id' => $ownClass->id,
            'teacher_id' => $teacher->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'total_marks' => 50,
            'status' => 'active',
        ]);

        Assignment::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'Other Class Homework',
            'description' => 'Should not appear',
            'subject' => 'Science',
            'class_id' => $otherClass->id,
            'teacher_id' => $teacher->id,
            'due_date' => now()->addDays(2)->toDateString(),
            'total_marks' => 40,
            'status' => 'active',
        ]);

        Assignment::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'Archived Task',
            'description' => 'Closed',
            'subject' => 'History',
            'class_id' => $ownClass->id,
            'teacher_id' => $teacher->id,
            'due_date' => now()->addDay()->toDateString(),
            'total_marks' => 20,
            'status' => 'archived',
        ]);

        Announcement::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'All Hands',
            'message' => 'School assembly tomorrow',
            'type' => 'info',
            'target_audience' => 'all',
            'date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $auth['user']->id,
        ]);

        Announcement::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'Student Notice',
            'message' => 'Bring PE kits',
            'type' => 'important',
            'target_audience' => 'students',
            'date' => now()->subDay()->toDateString(),
            'is_active' => true,
            'created_by' => $auth['user']->id,
        ]);

        Announcement::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'Parent Only',
            'message' => 'Fees reminder',
            'type' => 'warning',
            'target_audience' => 'parents',
            'date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $auth['user']->id,
        ]);

        Announcement::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'Inactive Notice',
            'message' => 'Old',
            'type' => 'info',
            'target_audience' => 'students',
            'date' => now()->toDateString(),
            'is_active' => false,
            'created_by' => $auth['user']->id,
        ]);

        $assignments = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/assignments')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $assignments);
        $this->assertSame($visibleAssignment->id, $assignments[0]['id']);
        $this->assertSame('Class Essay', $assignments[0]['title']);

        $announcements = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/announcements')
            ->assertOk()
            ->json('data');

        $titles = collect($announcements)->pluck('title')->all();
        $this->assertContains('All Hands', $titles);
        $this->assertContains('Student Notice', $titles);
        $this->assertNotContains('Parent Only', $titles);
        $this->assertNotContains('Inactive Notice', $titles);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/dashboard')
            ->assertOk()
            ->assertJsonPath('data.assignments.0.title', 'Class Essay')
            ->assertJsonPath('data.stats.open_assignments', 1)
            ->assertJsonFragment(['title' => 'All Hands']);
    }

    public function test_parents_children_endpoint_includes_guardian_linked_students(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'parent');
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);
        $guardian = Guardian::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
        ]);
        DB::table('guardian_student')->insert([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => 'parent',
            'is_primary' => true,
            'can_pickup' => true,
            'emergency_contact' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/parents/'.$auth['user']->id.'/children')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $payload);
        $this->assertSame($student->id, $payload[0]['id']);
    }
}
