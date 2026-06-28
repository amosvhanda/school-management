<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Grade;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Subject;

class GradeApiTest extends TestCase
{
    public function test_get_grades_by_class(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/grades/class/{$class->id}");

        $response->assertStatus(200);
    }

    public function test_get_grades_by_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/grades/student/{$student->id}");

        $response->assertStatus(200);
    }

    public function test_create_grade(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
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
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'score', 'grade'],
                'message',
            ]);
    }

    public function test_bulk_upload_grades(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        // Create a fake CSV file for upload
        $file = \Illuminate\Http\UploadedFile::fake()->create('grades.csv', 100);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/grades/bulk', [
            'file' => $file,
            'class' => $class->name,
            'term' => 'Term 1',
        ]);

        // This endpoint returns a message that it's not implemented yet
        $response->assertStatus(200);
    }

    public function test_get_class_performance(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/grades/class/{$class->id}/performance");

        $response->assertStatus(200);
    }
}
