<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\EnrollmentApplication;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreskoolPeopleParityApiTest extends TestCase
{
    public function test_enrollment_status_transition(): void
    {
        $auth = $this->createAuthenticatedUser();
        $application = EnrollmentApplication::create([
            'school_id' => $auth['school']->id,
            'first_name' => 'Ada',
            'surname' => 'Ncube',
            'date_of_birth' => '2012-01-15',
            'gender' => 'female',
            'phone' => '+263771111110',
            'address' => '1 Test Road',
            'grade_applying_for' => 'Form 1',
            'academic_year' => (string) now()->year,
            'status' => 'pending',
            'guardian_first_name' => 'Parent',
            'guardian_surname' => 'Ncube',
            'guardian_phone' => '+263771111111',
            'guardian_relationship' => 'parent',
            'guardian_address' => '1 Test Road',
            'emergency_contact' => 'Parent Ncube',
            'emergency_phone' => '+263771111112',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/enrollment-applications/{$application->id}/status", [
            'status' => 'under_review',
            'notes' => 'Docs received',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'under_review');

        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'status' => 'under_review',
        ]);
    }

    public function test_enrollment_status_cannot_approve_via_status_endpoint(): void
    {
        $auth = $this->createAuthenticatedUser();
        $application = EnrollmentApplication::create([
            'school_id' => $auth['school']->id,
            'first_name' => 'Bea',
            'surname' => 'Dube',
            'date_of_birth' => '2011-06-01',
            'gender' => 'female',
            'phone' => '+263772222220',
            'address' => '2 Test Road',
            'grade_applying_for' => 'Form 1',
            'academic_year' => (string) now()->year,
            'status' => 'pending',
            'guardian_first_name' => 'Parent',
            'guardian_surname' => 'Dube',
            'guardian_phone' => '+263772222222',
            'guardian_relationship' => 'parent',
            'guardian_address' => '2 Test Road',
            'emergency_contact' => 'Parent Dube',
            'emergency_phone' => '+263772222223',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/enrollment-applications/{$application->id}/status", [
            'status' => 'approved',
        ])->assertStatus(422);
    }

    public function test_students_documents_index(): void
    {
        Storage::fake('public');
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        StudentDocument::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'name' => 'Birth Certificate.pdf',
            'type' => 'birth_certificate',
            'path' => 'students/docs/birth.pdf',
            'size' => 1024,
            'is_favourite' => true,
            'verified_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/students/documents');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Birth Certificate.pdf')
            ->assertJsonPath('data.0.type', 'birth_certificate');
    }

    public function test_promotion_preview(): void
    {
        $auth = $this->createAuthenticatedUser();
        Student::factory()->count(2)->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);
        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'inactive',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/students/promotion/preview?academic_year='.now()->year);

        $response->assertOk()
            ->assertJsonPath('data.eligible', 2);
    }

    public function test_attendance_student_summary_includes_month_records(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
        ]);

        $month = now()->format('Y-m');
        Attendance::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => now()->startOfMonth()->toDateString(),
            'status' => 'present',
        ]);
        Attendance::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => now()->startOfMonth()->addDay()->toDateString(),
            'status' => 'absent',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/attendance/student/{$student->id}/summary?month={$month}");

        $response->assertOk()
            ->assertJsonPath('data.present', 1)
            ->assertJsonPath('data.absent', 1)
            ->assertJsonCount(2, 'data.records');
    }

    public function test_create_student_with_preskool_parity_fields_and_draft_status(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students', [
            'firstName' => 'Chipo',
            'surname' => 'Moyo',
            'class_id' => $class->id,
            'status' => 'inactive',
            'mother_tongue' => 'Shona',
            'languages_known' => 'English, Shona',
            'siblings' => ['Tariro', 'Tinashe'],
            'father_name' => 'Tendai Moyo',
            'blood_group' => 'O+',
            'religion' => 'Christian',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.first_name', 'Chipo')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('students', [
            'id' => $response->json('data.id'),
            'mother_tongue' => 'Shona',
            'languages_known' => 'English, Shona',
            'father_name' => 'Tendai Moyo',
            'blood_group' => 'O+',
            'status' => 'inactive',
        ]);
    }

    public function test_draft_student_can_omit_class(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students', [
            'firstName' => 'Draft',
            'surname' => 'Student',
            'status' => 'inactive',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'inactive');
    }
}
