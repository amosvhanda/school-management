<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\FeeStructure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentApiTest extends TestCase
{
    public function test_get_students_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/students');

        $response->assertStatus(200);
    }

    public function test_create_student_with_guardian_links_pivot(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students', [
            'firstName' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'dateOfBirth' => '2010-01-01',
            'gender' => 'male',
            'class' => $class->name,
            'class_id' => $class->id,
            'guardian' => [
                'firstName' => 'Mary',
                'surname' => 'Moyo',
                'phone' => '+263771234567',
                'email' => 'mary.moyo@example.com',
                'relationship' => 'mother',
            ],
        ]);

        $response->assertStatus(201);

        $studentId = $response->json('data.id');

        $guardians = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$studentId}/guardians");

        $guardians->assertOk()->assertJsonCount(1);
    }

    public function test_create_student_with_existing_guardian_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $guardian = \App\Models\Guardian::factory()->create(['school_id' => $auth['school']->id]);
        $studentPayload = [
            'firstName' => 'Jane',
            'surname' => 'Smith',
            'dateOfBirth' => '2011-05-05',
            'gender' => 'female',
            'class' => $class->name,
            'class_id' => $class->id,
            'guardian_id' => $guardian->id,
            'guardian' => ['relationship' => 'father'],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students', $studentPayload);

        $response->assertStatus(201);

        $studentId = $response->json('data.id');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$studentId}/guardians")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $guardian->id);
    }

    public function test_create_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/students', [
            'firstName' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'dateOfBirth' => '2010-01-01',
            'gender' => 'male',
            'class' => $class->name,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'first_name', 'last_name'],
                'message',
            ]);
    }

    public function test_get_student_by_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'first_name', 'last_name']]);
    }

    public function test_update_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/v1/students/{$student->id}", [
            'first_name' => 'Jane',
            'last_name' => 'Updated',
        ]);

        $response->assertStatus(200);
    }

    public function test_delete_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->deleteJson("/api/v1/students/{$student->id}");

        $response->assertStatus(200);
    }

    public function test_get_student_performance(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/students/{$student->id}/performance");

        $response->assertStatus(200);
    }

    public function test_get_student_invoices(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/students/{$student->id}/invoices");

        $response->assertStatus(200);
    }

    public function test_create_student_invoice(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson("/api/v1/students/{$student->id}/invoices", [
            'amount' => 1000.00,
            'description' => 'Tuition fee for Term 1',
            'dueDate' => now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
    }

    public function test_promote_students(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $newClass = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/students/promote', [
            'student_ids' => [$student->id],
            'new_class_id' => $newClass->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_bulk_invoices(): void
    {
        $auth = $this->createAuthenticatedUser();
        $students = Student::factory()->count(2)->create(['school_id' => $auth['school']->id]);
        $feeStructure = FeeStructure::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/students/bulk/invoices', [
            'studentIds' => $students->pluck('id')->toArray(),
            'feeStructureId' => $feeStructure->id,
            'amount' => 1000.00,
            'description' => 'Bulk invoice',
            'dueDate' => now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
    }

    public function test_bulk_status_update(): void
    {
        $auth = $this->createAuthenticatedUser();
        $students = Student::factory()->count(2)->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/students/bulk/status', [
            'studentIds' => $students->pluck('id')->toArray(),
            'status' => 'active',
        ]);

        $response->assertStatus(200);
    }

    public function test_upload_student_documents(): void
    {
        Storage::fake('public');

        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/students/{$student->id}/documents", [
            'documents' => [
                UploadedFile::fake()->create('birth-certificate.pdf', 100, 'application/pdf'),
            ],
            'type' => 'birth_certificate',
        ]);

        $response->assertCreated()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'birth_certificate');
    }
}
