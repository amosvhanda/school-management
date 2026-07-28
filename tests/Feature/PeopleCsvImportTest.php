<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PeopleCsvImportTest extends TestCase
{
    public function test_download_student_import_template(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->get('/api/v1/imports/students/template');

        $response->assertOk();
        $this->assertStringContainsString('first_name', $response->streamedContent());
        $this->assertStringContainsString('class', $response->streamedContent());
    }

    public function test_import_students_creates_and_updates_by_student_number(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'name' => 'Form 1A',
        ]);

        $csv = implode("\n", [
            'student_number,first_name,last_name,class,gender',
            'IMP-001,Alice,Moyo,Form 1A,female',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->post('/api/v1/imports/students', [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.failed', 0);

        $this->assertDatabaseHas('students', [
            'school_id' => $auth['school']->id,
            'student_number' => 'IMP-001',
            'first_name' => 'Alice',
            'class_id' => $class->id,
        ]);

        $updateCsv = implode("\n", [
            'student_number,first_name,last_name,class,gender',
            'IMP-001,Alice,Ndlovu,Form 1A,female',
        ]);

        $update = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->post('/api/v1/imports/students', [
            'file' => UploadedFile::fake()->createWithContent('students-update.csv', $updateCsv),
        ]);

        $update->assertOk()
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.created', 0);

        $this->assertDatabaseHas('students', [
            'student_number' => 'IMP-001',
            'last_name' => 'Ndlovu',
        ]);
        $this->assertSame(1, Student::where('school_id', $auth['school']->id)->where('student_number', 'IMP-001')->count());
    }

    public function test_import_students_fails_row_when_class_missing(): void
    {
        $auth = $this->createAuthenticatedUser();

        $csv = implode("\n", [
            'first_name,last_name,class',
            'Bob,Chipo,Missing Class',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->post('/api/v1/imports/students', [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.created', 0);

        $this->assertStringContainsString('not found', $response->json('data.errors.0.message'));
    }

    public function test_import_teachers_upserts_by_email(): void
    {
        $auth = $this->createAuthenticatedUser();

        $csv = implode("\n", [
            'first_name,last_name,email,subject',
            'Tendai,Gumbo,tendai@school.test,Math',
        ]);

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->post('/api/v1/imports/teachers', [
            'file' => UploadedFile::fake()->createWithContent('teachers.csv', $csv),
        ]);

        $create->assertOk()->assertJsonPath('data.created', 1);

        $updateCsv = implode("\n", [
            'first_name,last_name,email,subject',
            'Tendai,Gumbo,tendai@school.test,Science',
        ]);

        $update = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->post('/api/v1/imports/teachers', [
            'file' => UploadedFile::fake()->createWithContent('teachers-update.csv', $updateCsv),
        ]);

        $update->assertOk()->assertJsonPath('data.updated', 1);

        $this->assertSame(1, Teacher::where('school_id', $auth['school']->id)->where('email', 'tendai@school.test')->count());
        $this->assertDatabaseHas('teachers', [
            'email' => 'tendai@school.test',
            'subject' => 'Science',
        ]);
    }

    public function test_import_employees_creates_records(): void
    {
        $auth = $this->createAuthenticatedUser();

        $csv = implode("\n", [
            'employee_number,first_name,last_name,email',
            'EMP-IMPORT-1,Grace,Sibanda,grace@school.test',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->post('/api/v1/imports/employees', [
            'file' => UploadedFile::fake()->createWithContent('employees.csv', $csv),
        ]);

        $response->assertOk()->assertJsonPath('data.created', 1);

        $this->assertDatabaseHas('employees', [
            'school_id' => $auth['school']->id,
            'employee_number' => 'EMP-IMPORT-1',
            'first_name' => 'Grace',
        ]);
        $this->assertSame(1, Employee::where('employee_number', 'EMP-IMPORT-1')->count());
    }
}
