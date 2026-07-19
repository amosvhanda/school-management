<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use Tests\TestCase;

class AcademicApiTest extends TestCase
{
    public function test_grade_levels_crud(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/grade-levels', [
            'name' => 'Form 1',
            'code' => 'F1',
            'order' => 1,
        ]);

        $create->assertCreated();
        $id = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/grade-levels')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/grade-levels/{$id}", ['description' => 'First form'])
            ->assertOk();
    }

    public function test_terms_crud(): void
    {
        $auth = $this->createAuthenticatedUser();
        $year = now()->year;

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/terms', [
            'name' => 'Term 1',
            'academic_year' => "{$year}-".($year + 1),
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'order' => 1,
            'is_current' => true,
        ]);

        $create->assertCreated();
        $id = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/terms/current')
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/terms/{$id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Term 1');
    }

    public function test_setting_term_current_deactivates_other_terms(): void
    {
        $auth = $this->createAuthenticatedUser();
        $schoolId = $auth['school']->id;
        $year = now()->year.'-'.(now()->year + 1);

        $term1 = Term::factory()->create([
            'school_id' => $schoolId,
            'name' => 'Term 1',
            'academic_year' => $year,
            'order' => 1,
            'is_current' => true,
            'is_active' => true,
        ]);
        $term2 = Term::factory()->create([
            'school_id' => $schoolId,
            'name' => 'Term 2',
            'academic_year' => $year,
            'order' => 2,
            'is_current' => false,
            'is_active' => true,
        ]);
        $term3 = Term::factory()->create([
            'school_id' => $schoolId,
            'name' => 'Term 3',
            'academic_year' => $year,
            'order' => 3,
            'is_current' => false,
            'is_active' => true,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/terms/{$term2->id}", [
                'is_current' => true,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $term2->id)
            ->assertJsonPath('data.is_current', true)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('terms', [
            'id' => $term2->id,
            'is_current' => 1,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('terms', [
            'id' => $term1->id,
            'is_current' => 0,
            'is_active' => 0,
        ]);
        $this->assertDatabaseHas('terms', [
            'id' => $term3->id,
            'is_current' => 0,
            'is_active' => 0,
        ]);
    }

    public function test_rooms_crud(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/rooms', [
            'name' => 'Room 101',
            'code' => 'R101',
            'type' => 'classroom',
            'capacity' => 35,
        ]);

        $create->assertCreated();
        $id = $create->json('id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/rooms')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->deleteJson("/api/v1/rooms/{$id}")
            ->assertOk();
    }

    public function test_teacher_assignments_crud(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/teacher-assignments', [
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'role' => 'subject_teacher',
        ]);

        $create->assertCreated();
        $id = $create->json('id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/teacher-assignments')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->deleteJson("/api/v1/teacher-assignments/{$id}")
            ->assertOk();
    }

    public function test_grade_level_list_includes_created_level(): void
    {
        $auth = $this->createAuthenticatedUser();
        GradeLevel::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Form 2']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/grade-levels');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Form 2']);
    }

    public function test_term_factory_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        Term::factory()->create(['school_id' => $auth['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/terms')
            ->assertOk();
    }

    public function test_room_factory_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        Room::factory()->create(['school_id' => $auth['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/rooms')
            ->assertOk();
    }
}
