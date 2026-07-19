<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Services\StudentAdmissionService;
use Tests\TestCase;

class StudentNumberGenerationTest extends TestCase
{
    public function test_generates_school_code_year_sequence_format(): void
    {
        $school = School::factory()->create(['code' => 'MUF001']);
        $service = app(StudentAdmissionService::class);

        $number = $service->generateStudentNumber($school->id);

        $this->assertSame('MUF001-'.date('Y').'-0001', $number);
    }

    public function test_increments_sequence_for_same_school_and_year(): void
    {
        $school = School::factory()->create(['code' => 'MUF001']);
        $year = date('Y');

        Student::factory()->create([
            'school_id' => $school->id,
            'student_number' => "MUF001-{$year}-0003",
        ]);

        $number = app(StudentAdmissionService::class)->generateStudentNumber($school->id);

        $this->assertSame("MUF001-{$year}-0004", $number);
    }

    public function test_v1_student_create_assigns_generated_number(): void
    {
        $auth = $this->createAuthenticatedUser();
        $auth['school']->update(['code' => 'MUF001']);
        $class = \App\Models\ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students', [
            'firstName' => 'Nyasha',
            'surname' => 'Chiremba',
            'dateOfBirth' => '2010-05-01',
            'gender' => 'female',
            'class' => $class->name,
            'class_id' => $class->id,
            'guardian' => [
                'firstName' => 'Mary',
                'surname' => 'Chiremba',
                'phone' => '+263771234567',
                'relationship' => 'mother',
            ],
        ]);

        $response->assertCreated();
        $this->assertSame('MUF001-'.date('Y').'-0001', $response->json('data.student_number'));
    }
}
