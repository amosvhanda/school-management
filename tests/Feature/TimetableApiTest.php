<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Timetable;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;

class TimetableApiTest extends TestCase
{
    public function test_get_timetable_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/timetable');

        $response->assertStatus(200);
    }

    public function test_create_timetable_entry(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/timetable', [
            'class_id' => $class->id,
            'subject' => $subject->name,
            'teacher_id' => $teacher->id,
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'class_id', 'subject', 'day'],
                'message',
            ]);
    }

    public function test_update_timetable_entry(): void
    {
        $auth = $this->createAuthenticatedUser();
        $timetable = Timetable::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/v1/timetable/{$timetable->id}", [
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertStatus(200);
    }

    public function test_delete_timetable_entry(): void
    {
        $auth = $this->createAuthenticatedUser();
        $timetable = Timetable::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->deleteJson("/api/v1/timetable/{$timetable->id}");

        $response->assertStatus(200);
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

        $response->assertOk()
            ->assertJsonPath('data.created', 1)
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
            ->assertJsonPath('data.created', 2)
            ->assertJsonStructure([
                'data' => ['classes_processed', 'created', 'skipped', 'conflicts', 'classes', 'entries'],
            ]);
    }
}
