<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\Subject;
use App\Services\SchoolConfigurationService;
use Tests\TestCase;

class GradeApiTest extends TestCase
{
    public function test_get_grades_by_class(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'class' => $class->name,
        ]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Mathematics']);
        $grade = Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'score' => 85,
            'total' => 100,
        ]);

        $otherClass = ClassModel::factory()->create();
        $otherStudent = Student::factory()->create([
            'school_id' => $otherClass->school_id,
            'class_id' => $otherClass->id,
            'class' => $otherClass->name,
        ]);
        $otherSubject = Subject::factory()->create(['school_id' => $otherClass->school_id]);
        Grade::factory()->create([
            'school_id' => $otherClass->school_id,
            'class_id' => $otherClass->id,
            'student_id' => $otherStudent->id,
            'subject_id' => $otherSubject->id,
            'subject' => $otherSubject->name,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/grades/class/{$class->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $grade->id)
            ->assertJsonPath('data.0.student_id', $student->id);
    }

    public function test_get_grades_by_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $grade = Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/grades/student/{$student->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $grade->id)
            ->assertJsonPath('data.0.student_id', $student->id);
    }

    public function test_create_grade(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(SchoolConfigurationService::class)->initializeDefaultGradingScale($auth['school']);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id, 'is_active' => true]);
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'class' => $class->name,
            'grade_level_id' => $gradeLevel->id,
        ]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/grades', [
            'student_id' => $student->id,
            'subject' => $subject->name,
            'subject_id' => $subject->id,
            'score' => 85.5,
            'total' => 100,
            'grade' => 'A',
            'term' => 'Term 1',
            'year' => now()->year,
            'class_id' => $class->id,
            'grade_level_id' => $gradeLevel->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'score', 'grade', 'student_id', 'class_id'],
                'message',
            ])
            ->assertJsonPath('data.student_id', $student->id)
            ->assertJsonPath('data.class_id', $class->id)
            ->assertJsonPath('data.grade', 'A');

        $gradeId = $response->json('data.id');

        $this->assertDatabaseHas('grades', [
            'id' => $gradeId,
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'score' => 85.5,
            'total' => 100,
            'grade' => 'A',
            'term' => 'Term 1',
        ]);
    }

    public function test_bulk_upload_grades(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(SchoolConfigurationService::class)->initializeDefaultGradingScale($auth['school']);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id, 'is_active' => true]);
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'class' => $class->name,
            'grade_level_id' => $gradeLevel->id,
        ]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Mathematics']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/grades/bulk', [
            'class_id' => $class->id,
            'term' => 'Term 1',
            'year' => now()->year,
            'assessment_type' => 'other',
            'grades' => [[
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'assessment_type' => 'other',
                'score' => 88,
                'total' => 100,
                'class_id' => $class->id,
                'grade_level_id' => $gradeLevel->id,
                'term' => 'Term 1',
                'year' => now()->year,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.errors', [])
            ->assertJsonPath('message', 'Bulk upload completed successfully');

        $this->assertDatabaseHas('grades', [
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'score' => 88,
            'grade' => 'A',
        ]);
    }

    public function test_get_class_performance(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $studentA = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'class' => $class->name,
        ]);
        $studentB = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'class' => $class->name,
        ]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Mathematics']);

        Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'student_id' => $studentA->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'score' => 80,
            'total' => 100,
        ]);
        Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'student_id' => $studentB->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'score' => 90,
            'total' => 100,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/grades/class/{$class->id}/performance");

        $response->assertOk()
            ->assertJsonPath('data.average_score', 85)
            ->assertJsonPath('data.total_students', 2)
            ->assertJsonPath('data.total_grades', 2)
            ->assertJsonPath('data.subject_averages.Mathematics', 85);
    }
}
