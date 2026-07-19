<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Timetable;
use Tests\TestCase;

class TimetableApiTest extends TestCase
{
    public function test_get_timetable_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);
        $entry = Timetable::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'teacher_id' => $teacher->id,
        ]);

        $otherEntry = Timetable::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/timetable');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $entry->id)
            ->assertJsonPath('data.0.school_id', $auth['school']->id);

        $this->assertNotContains($otherEntry->id, array_column($response->json('data'), 'id'));
    }

    public function test_create_timetable_entry(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/timetable', [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'teacher_id' => $teacher->id,
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'class_id', 'subject', 'day', 'teacher_id', 'school_id'],
                'message',
            ])
            ->assertJsonPath('data.class_id', $class->id)
            ->assertJsonPath('data.subject_id', $subject->id)
            ->assertJsonPath('data.subject.name', $subject->name)
            ->assertJsonPath('data.teacher_id', $teacher->id)
            ->assertJsonPath('data.school_id', $auth['school']->id);

        $entryId = $response->json('data.id');

        $this->assertDatabaseHas('timetable', [
            'id' => $entryId,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'teacher_id' => $teacher->id,
            'day' => 'Monday',
            'school_id' => $auth['school']->id,
        ]);
    }

    public function test_update_timetable_entry(): void
    {
        $auth = $this->createAuthenticatedUser();
        $timetable = Timetable::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/timetable/{$timetable->id}", [
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $timetable->id)
            ->assertJsonPath('data.start_time', '09:00')
            ->assertJsonPath('data.end_time', '10:00');

        $this->assertDatabaseHas('timetable', [
            'id' => $timetable->id,
        ]);
        $this->assertSame('09:00', $timetable->fresh()->start_time?->format('H:i'));
        $this->assertSame('10:00', $timetable->fresh()->end_time?->format('H:i'));
    }

    public function test_delete_timetable_entry(): void
    {
        $auth = $this->createAuthenticatedUser();
        $timetable = Timetable::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/timetable/{$timetable->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Timetable slot deleted successfully');

        $this->assertDatabaseMissing('timetable', [
            'id' => $timetable->id,
        ]);
    }

    public function test_generate_timetable(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'role' => 'subject_teacher',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/timetable/generate', [
            'class_id' => $class->id,
            'replace_existing' => true,
        ]);

        // Full week grid: 5 days × 6 periods with one subject assignment.
        $response->assertOk()
            ->assertJsonPath('data.created', 30)
            ->assertJsonStructure([
                'data' => ['created', 'skipped', 'conflicts', 'entries'],
            ]);
    }

    public function test_bulk_generate_timetable_for_all_classes(): void
    {
        $auth = $this->createAuthenticatedUser();
        $classA = ClassModel::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Form 1A']);
        $classB = ClassModel::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Form 1B']);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $teacherA = Teacher::factory()->create(['school_id' => $auth['school']->id]);
        $teacherB = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        foreach ([[$classA, $teacherA], [$classB, $teacherB]] as [$class, $teacher]) {
            TeacherAssignment::create([
                'school_id' => $auth['school']->id,
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'subject_id' => $subject->id,
                'role' => 'subject_teacher',
                'is_active' => true,
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/timetable/generate-bulk', [
            'all_classes' => true,
            'replace_existing' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.classes_processed', 2)
            ->assertJsonPath('data.created', 60)
            ->assertJsonStructure([
                'data' => ['classes_processed', 'created', 'skipped', 'conflicts', 'classes', 'entries'],
            ]);
    }

    public function test_student_sees_only_their_class_timetable(): void
    {
        $school = \App\Models\School::factory()->create();
        $classA = ClassModel::factory()->create(['school_id' => $school->id, 'name' => 'Form 1A']);
        $classB = ClassModel::factory()->create(['school_id' => $school->id, 'name' => 'Form 1B']);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);

        $mine = Timetable::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classA->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'teacher_id' => $teacher->id,
            'day' => 'Monday',
        ]);
        Timetable::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classB->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'teacher_id' => $teacher->id,
            'day' => 'Tuesday',
        ]);

        $auth = $this->createAuthenticatedUser('student', $school->id);
        \App\Models\Student::factory()->create([
            'school_id' => $school->id,
            'user_id' => $auth['user']->id,
            'class_id' => $classA->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/timetable');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('meta.scope', 'student');
    }

    public function test_teacher_sees_only_their_assigned_slots(): void
    {
        $school = \App\Models\School::factory()->create();
        $class = ClassModel::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacherA = Teacher::factory()->create(['school_id' => $school->id]);
        $teacherB = Teacher::factory()->create(['school_id' => $school->id]);

        $mine = Timetable::factory()->create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'teacher_id' => $teacherA->id,
            'day' => 'Monday',
        ]);
        Timetable::factory()->create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'teacher_id' => $teacherB->id,
            'day' => 'Tuesday',
        ]);

        $auth = $this->createAuthenticatedUser('teacher', $school->id);
        $teacherA->update(['user_id' => $auth['user']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/timetable');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('meta.scope', 'teacher');
    }

    public function test_teacher_cannot_modify_timetable(): void
    {
        $school = \App\Models\School::factory()->create();
        $class = ClassModel::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        $auth = $this->createAuthenticatedUser('teacher', $school->id);
        $teacher->update(['user_id' => $auth['user']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/timetable', [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'teacher_id' => $teacher->id,
        ]);

        $response->assertForbidden();
    }

    public function test_parent_cannot_see_school_wide_timetable(): void
    {
        $school = \App\Models\School::factory()->create();
        $class = ClassModel::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);

        Timetable::factory()->create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'teacher_id' => $teacher->id,
            'day' => 'Monday',
        ]);

        $auth = $this->createAuthenticatedUser('parent', $school->id);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/timetable')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.scope', 'none');
    }
}
