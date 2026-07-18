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
            ->assertJsonPath('data.start_time', $timetable->fresh()->start_time?->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z'));

        $this->assertDatabaseHas('timetable', [
            'id' => $timetable->id,
        ]);
        $this->assertSame('09:00:00', $timetable->fresh()->start_time?->format('H:i:s'));
        $this->assertSame('10:00:00', $timetable->fresh()->end_time?->format('H:i:s'));
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
