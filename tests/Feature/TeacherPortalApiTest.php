<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherNotification;
use Tests\TestCase;

class TeacherPortalApiTest extends TestCase
{
    protected function teacherAuth(): array
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'email' => $auth['user']->email,
            'department' => 'Sciences',
        ]);
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::query()->first()
            ?? Subject::factory()->create(['school_id' => $auth['school']->id]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);

        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
        ]);

        return [
            ...$auth,
            'teacher' => $teacher,
            'class' => $class,
            'subject' => $subject,
            'student' => $student,
            'headers' => ['Authorization' => 'Bearer '.$auth['token']],
        ];
    }

    public function test_dashboard_and_classes(): void
    {
        $ctx = $this->teacherAuth();

        $this->withHeaders($ctx['headers'])
            ->getJson('/api/v1/teacher-portal/dashboard')
            ->assertOk()
            ->assertJsonPath('data.classes_count', 1);

        $this->withHeaders($ctx['headers'])
            ->getJson('/api/v1/teacher-portal/classes')
            ->assertOk()
            ->assertJsonPath('data.0.class_id', $ctx['class']->id);
    }

    public function test_lesson_plan_lifecycle(): void
    {
        $ctx = $this->teacherAuth();

        $create = $this->withHeaders($ctx['headers'])->postJson('/api/v1/teacher-portal/lesson-plans', [
            'title' => 'Fractions intro',
            'plan_type' => 'lesson',
            'topic' => 'Fractions',
            'objectives' => 'Understand fractions',
            'class_id' => $ctx['class']->id,
            'subject_id' => $ctx['subject']->id,
        ]);
        $create->assertCreated();
        $id = $create->json('data.id');

        $this->withHeaders($ctx['headers'])
            ->putJson("/api/v1/teacher-portal/lesson-plans/{$id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('lesson_plans', [
            'id' => $id,
            'teacher_id' => $ctx['teacher']->id,
            'status' => 'completed',
        ]);
    }

    public function test_ai_generate_stub(): void
    {
        $ctx = $this->teacherAuth();

        $this->withHeaders($ctx['headers'])
            ->postJson('/api/v1/teacher-portal/ai/generate', [
                'task' => 'lesson_plan',
                'context' => ['subject' => 'Math', 'topic' => 'Algebra', 'grade' => 'Form 2'],
            ])
            ->assertOk()
            ->assertJsonPath('data.task', 'lesson_plan')
            ->assertJsonPath('data.meta.provider', 'stub');
    }

    public function test_behaviour_and_leave_and_notifications(): void
    {
        $ctx = $this->teacherAuth();

        $this->withHeaders($ctx['headers'])->postJson('/api/v1/teacher-portal/behaviour', [
            'student_id' => $ctx['student']->id,
            'points' => 2,
            'category' => 'positive',
            'description' => 'Helped peers',
        ])->assertCreated();

        $this->withHeaders($ctx['headers'])->postJson('/api/v1/teacher-portal/leave', [
            'type' => 'annual',
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'reason' => 'Personal',
            'request_replacement' => true,
        ])->assertCreated();

        TeacherNotification::create([
            'school_id' => $ctx['school']->id,
            'user_id' => $ctx['user']->id,
            'type' => 'info',
            'title' => 'Welcome',
            'body' => 'Portal ready',
        ]);

        $this->withHeaders($ctx['headers'])
            ->getJson('/api/v1/teacher-portal/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_export_class_list_csv(): void
    {
        $ctx = $this->teacherAuth();

        $this->withHeaders($ctx['headers'])
            ->get('/api/v1/teacher-portal/export?type=class_list&format=csv&class_id='.$ctx['class']->id)
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_attendance_left_early_status_accepted(): void
    {
        $ctx = $this->teacherAuth();

        $this->withHeaders($ctx['headers'])->postJson('/api/v1/attendance', [
            'class_id' => $ctx['class']->id,
            'date' => now()->toDateString(),
            'records' => [[
                'student_id' => $ctx['student']->id,
                'status' => 'left_early',
                'remarks' => 'Collected early',
            ]],
        ])->assertCreated();

        $this->assertDatabaseHas('attendance', [
            'student_id' => $ctx['student']->id,
            'status' => 'left_early',
        ]);
    }

    public function test_attendance_lock_sets_submitted_and_locked_on_sqlite(): void
    {
        $ctx = $this->teacherAuth();
        $date = now()->toDateString();

        $this->withHeaders($ctx['headers'])->postJson('/api/v1/attendance', [
            'class_id' => $ctx['class']->id,
            'date' => $date,
            'records' => [[
                'student_id' => $ctx['student']->id,
                'status' => 'present',
            ]],
        ])->assertCreated();

        $this->withHeaders($ctx['headers'])
            ->postJson('/api/v1/teacher-portal/attendance/lock', [
                'class_id' => $ctx['class']->id,
                'date' => $date,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Attendance locked');

        $this->assertDatabaseHas('attendance', [
            'student_id' => $ctx['student']->id,
            'class_id' => $ctx['class']->id,
            'locked_by' => $ctx['user']->id,
        ]);

        $this->assertNotNull(
            \App\Models\Attendance::query()
                ->where('student_id', $ctx['student']->id)
                ->value('locked_at')
        );
        $this->assertNotNull(
            \App\Models\Attendance::query()
                ->where('student_id', $ctx['student']->id)
                ->value('submitted_at')
        );

        $this->withHeaders($ctx['headers'])->postJson('/api/v1/attendance', [
            'class_id' => $ctx['class']->id,
            'date' => $date,
            'overwrite' => true,
            'records' => [[
                'student_id' => $ctx['student']->id,
                'status' => 'absent',
            ]],
        ])->assertStatus(423)
            ->assertJsonPath('error_code', 'attendance_locked');

        $this->assertDatabaseHas('attendance', [
            'student_id' => $ctx['student']->id,
            'status' => 'present',
        ]);
    }
}
