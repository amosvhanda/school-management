<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use Tests\TestCase;

class SchoolAssessmentApiTest extends TestCase
{
    public function test_create_and_list_tests(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);
        $term = Term::factory()->create(['school_id' => $auth['school']->id]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'role' => 'subject_teacher',
            'is_active' => true,
        ]);

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/tests', [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
            'name' => 'Chapter 3 Test',
            'test_date' => now()->addDays(3)->toDateString(),
            'total_marks' => 50,
            'academic_year' => $term->academic_year,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Chapter 3 Test');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/tests')
            ->assertOk();
    }

    public function test_record_test_results(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
        ]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);

        $test = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/tests', [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'name' => 'Weekly Quiz',
            'test_date' => now()->toDateString(),
            'total_marks' => 20,
            'academic_year' => now()->year.'-'.(now()->year + 1),
        ]);

        $testId = $test->json('data.id');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/tests/{$testId}/results", [
            'results' => [
                ['student_id' => $student->id, 'marks_obtained' => 16],
            ],
        ]);

        $response->assertOk();
    }
}
