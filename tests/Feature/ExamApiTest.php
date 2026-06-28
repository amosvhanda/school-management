<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use Tests\TestCase;

class ExamApiTest extends TestCase
{
    public function test_get_exams_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        Exam::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/exams');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_create_exam(): void
    {
        $auth = $this->createAuthenticatedUser();
        $term = Term::factory()->create(['school_id' => $auth['school']->id]);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/exams', [
            'term_id' => $term->id,
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subject->id,
            'name' => 'Mid-Term Mathematics',
            'exam_date' => now()->addWeek()->toDateString(),
            'total_marks' => 100,
            'academic_year' => $term->academic_year,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Mid-Term Mathematics');
    }

    public function test_record_exam_results(): void
    {
        $auth = $this->createAuthenticatedUser();
        $term = Term::factory()->create(['school_id' => $auth['school']->id]);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $gradeLevel->id,
        ]);

        $exam = Exam::factory()->create([
            'school_id' => $auth['school']->id,
            'term_id' => $term->id,
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subject->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/exams/{$exam->id}/results", [
            'results' => [
                ['student_id' => $student->id, 'marks_obtained' => 78],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Exam results recorded successfully');
    }

    public function test_get_student_exams(): void
    {
        $auth = $this->createAuthenticatedUser();
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $gradeLevel->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$student->id}/exams");

        $response->assertOk();
    }

    public function test_teacher_sees_only_assigned_subject_exams(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
        ]);

        $term = Term::factory()->create(['school_id' => $auth['school']->id]);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $assignedSubject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $otherSubject = Subject::factory()->create(['school_id' => $auth['school']->id]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $assignedSubject->id,
            'grade_level_id' => $gradeLevel->id,
            'role' => 'subject_teacher',
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $visibleExam = Exam::factory()->create([
            'school_id' => $auth['school']->id,
            'term_id' => $term->id,
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $assignedSubject->id,
        ]);

        Exam::factory()->create([
            'school_id' => $auth['school']->id,
            'term_id' => $term->id,
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $otherSubject->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/exams');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$visibleExam->id], $ids);
    }

    public function test_teacher_can_record_results_for_assigned_subject(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
        ]);

        $term = Term::factory()->create(['school_id' => $auth['school']->id]);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $gradeLevel->id,
        ]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'grade_level_id' => $gradeLevel->id,
            'role' => 'subject_teacher',
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $exam = Exam::factory()->create([
            'school_id' => $auth['school']->id,
            'term_id' => $term->id,
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subject->id,
            'is_published' => false,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/exams/{$exam->id}/results", [
            'results' => [
                ['student_id' => $student->id, 'marks_obtained' => 82],
            ],
        ])->assertOk();
    }

    public function test_teacher_cannot_record_results_for_unassigned_subject(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
        ]);

        $term = Term::factory()->create(['school_id' => $auth['school']->id]);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $gradeLevel->id,
        ]);

        $exam = Exam::factory()->create([
            'school_id' => $auth['school']->id,
            'term_id' => $term->id,
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subject->id,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/exams/{$exam->id}/results", [
            'results' => [
                ['student_id' => $student->id, 'marks_obtained' => 82],
            ],
        ])->assertForbidden();
    }
}
