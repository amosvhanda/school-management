<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    public function test_get_students_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $otherStudent = Student::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/students?all=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id)
            ->assertJsonPath('data.0.school_id', $auth['school']->id);

        $this->assertNotContains($otherStudent->id, array_column($response->json('data'), 'id'));
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
        $guardian = Guardian::factory()->create(['school_id' => $auth['school']->id]);
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
        $email = 'john.doe@example.com';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students', [
            'firstName' => 'John',
            'surname' => 'Doe',
            'email' => $email,
            'dateOfBirth' => '2010-01-01',
            'gender' => 'male',
            'class' => $class->name,
            'class_id' => $class->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'first_name', 'last_name', 'class_id', 'school_id', 'status'],
                'message',
            ])
            ->assertJsonPath('data.first_name', 'John')
            ->assertJsonPath('data.last_name', 'Doe')
            ->assertJsonPath('data.class_id', $class->id)
            ->assertJsonPath('data.school_id', $auth['school']->id)
            ->assertJsonPath('data.status', 'active');

        $studentId = $response->json('data.id');

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'full_name' => 'John Doe',
            'email' => $email,
            'class' => $class->name,
            'class_id' => $class->id,
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);
    }

    public function test_create_student_with_class_id_only(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students', [
            'firstName' => 'Jane',
            'surname' => 'Smith',
            'dateOfBirth' => '2011-06-15',
            'gender' => 'female',
            'class_id' => $class->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.class_id', $class->id);

        $this->assertDatabaseHas('students', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'class' => $class->name,
            'class_id' => $class->id,
            'school_id' => $auth['school']->id,
        ]);
    }

    public function test_get_student_by_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$student->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'first_name', 'last_name', 'school_id']])
            ->assertJsonPath('data.id', $student->id)
            ->assertJsonPath('data.school_id', $auth['school']->id);
    }

    public function test_update_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/students/{$student->id}", [
            'firstName' => 'Jane',
            'surname' => 'Updated',
            'status' => 'inactive',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.last_name', 'Updated')
            ->assertJsonPath('data.full_name', 'Jane Updated')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'first_name' => 'Jane',
            'last_name' => 'Updated',
            'full_name' => 'Jane Updated',
            'status' => 'inactive',
        ]);
    }

    public function test_delete_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/students/{$student->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Student deleted successfully');

        $this->assertSoftDeleted('students', [
            'id' => $student->id,
        ]);
    }

    public function test_get_student_performance(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Mathematics']);
        Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'score' => 80,
            'total' => 100,
        ]);
        Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'subject' => $subject->name,
            'score' => 90,
            'total' => 100,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$student->id}/performance");

        $response->assertOk()
            ->assertJsonPath('data.student.id', $student->id)
            ->assertJsonPath('data.average_score', 85)
            ->assertJsonPath('data.total_grades', 2);
    }

    public function test_get_student_invoices(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'currency' => $auth['school']->currency_default,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$student->id}/invoices");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $invoice->id)
            ->assertJsonPath('data.0.student_id', $student->id);
    }

    public function test_create_student_invoice(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/students/{$student->id}/invoices", [
            'amount' => 1000.00,
            'description' => 'Tuition fee for Term 1',
            'dueDate' => now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.student_id', $student->id)
            ->assertJsonPath('data.amount', '1000.00')
            ->assertJsonPath('data.balance', '1000.00')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('invoices', [
            'id' => $response->json('data.id'),
            'student_id' => $student->id,
            'school_id' => $auth['school']->id,
            'amount' => 1000,
            'balance' => 1000,
            'description' => 'Tuition fee for Term 1',
            'status' => 'pending',
        ]);
    }

    public function test_promote_students(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $newClass = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
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
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students/bulk/invoices', [
            'studentIds' => $students->pluck('id')->toArray(),
            'feeStructureId' => $feeStructure->id,
            'amount' => 1000.00,
            'description' => 'Bulk invoice',
            'dueDate' => now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('message', 'Invoices created for 2 students');

        $this->assertSame(2, Invoice::whereIn('student_id', $students->pluck('id'))->count());
    }

    public function test_bulk_status_update(): void
    {
        $auth = $this->createAuthenticatedUser();
        $students = Student::factory()->count(2)->create(['school_id' => $auth['school']->id]);
        $otherStudent = Student::factory()->create(['status' => 'active']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students/bulk/status', [
            'studentIds' => array_merge($students->pluck('id')->toArray(), [$otherStudent->id]),
            'status' => 'inactive',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.updated', 2)
            ->assertJsonPath('message', 'Status updated for 2 students');

        foreach ($students as $student) {
            $this->assertDatabaseHas('students', [
                'id' => $student->id,
                'status' => 'inactive',
            ]);
        }

        $this->assertDatabaseHas('students', [
            'id' => $otherStudent->id,
            'status' => 'active',
        ]);
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

    public function test_student_can_download_own_results_report_card(): void
    {
        $auth = $this->createAuthenticatedUser('student');
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'full_name' => 'Ada Lovelace',
            'student_number' => 'STU-1001',
        ]);

        Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'subject' => 'Mathematics',
            'score' => 82,
            'total' => 100,
            'grade' => 'A',
            'term' => 'Term 1',
            'year' => now()->year,
        ]);

        $html = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->get("/api/v1/students/{$student->id}/results/download?format=html");

        $html->assertOk();
        $this->assertStringContainsString('text/html', (string) $html->headers->get('content-type'));
        $this->assertStringContainsString('Ada Lovelace', $html->getContent());
        $this->assertStringContainsString('Mathematics', $html->getContent());

        $csv = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->get("/api/v1/students/{$student->id}/results/download?format=csv");

        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));
        $this->assertStringContainsString('Mathematics', $csv->streamedContent());
    }

    public function test_student_cannot_download_another_students_results(): void
    {
        $auth = $this->createAuthenticatedUser('student');
        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
        ]);
        $other = Student::factory()->create(['school_id' => $auth['school']->id]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->get("/api/v1/students/{$other->id}/results/download")
            ->assertForbidden();
    }
}
