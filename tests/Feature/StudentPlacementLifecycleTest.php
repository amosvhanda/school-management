<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\GradeLevel;
use App\Models\House;
use App\Models\Stream;
use App\Models\Student;
use Tests\TestCase;

class StudentPlacementLifecycleTest extends TestCase
{
    public function test_approve_requires_class_when_multiple_exist_and_places_stream_house(): void
    {
        $auth = $this->createAuthenticatedUser();
        $grade = GradeLevel::factory()->create([
            'school_id' => $auth['school']->id,
            'name' => 'Form 1',
            'order' => 1,
        ]);
        $stream = Stream::create([
            'school_id' => $auth['school']->id,
            'name' => 'Sciences',
            'code' => 'SCI',
            'is_active' => true,
        ]);
        $house = House::create([
            'school_id' => $auth['school']->id,
            'name' => 'Nkomo',
            'code' => 'NK',
            'is_active' => true,
        ]);
        ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $grade->id,
            'stream_id' => $stream->id,
            'name' => 'Form 1A',
            'capacity' => 40,
        ]);
        $classB = ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $grade->id,
            'stream_id' => $stream->id,
            'name' => 'Form 1B',
            'capacity' => 40,
        ]);

        $application = EnrollmentApplication::create([
            'school_id' => $auth['school']->id,
            'first_name' => 'Tariro',
            'surname' => 'Dube',
            'date_of_birth' => '2012-03-01',
            'gender' => 'female',
            'phone' => '+263771111111',
            'address' => 'Harare',
            'grade_applying_for' => 'Form 1',
            'academic_year' => (string) now()->year,
            'guardian_first_name' => 'Parent',
            'guardian_surname' => 'Dube',
            'guardian_phone' => '+263772222222',
            'guardian_relationship' => 'mother',
            'guardian_address' => 'Harare',
            'emergency_contact' => 'Uncle',
            'emergency_phone' => '+263773333333',
            'status' => 'pending',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/enrollment-applications/{$application->id}/approve")
            ->assertStatus(422);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/enrollment-applications/{$application->id}/approve", [
                'class_id' => $classB->id,
                'stream_id' => $stream->id,
                'house_id' => $house->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.student.class_id', $classB->id)
            ->assertJsonPath('data.student.stream_id', $stream->id)
            ->assertJsonPath('data.student.house_id', $house->id);

        $studentId = $response->json('data.student.id');
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $studentId,
            'class_id' => $classB->id,
            'stream_id' => $stream->id,
            'house_id' => $house->id,
            'status' => 'active',
            'reason' => 'admission',
        ]);
    }

    public function test_mid_year_placement_closes_prior_enrollment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $grade = GradeLevel::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Form 2', 'order' => 2]);
        $from = ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $grade->id,
            'name' => 'Form 2A',
            'capacity' => 30,
        ]);
        $to = ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $grade->id,
            'name' => 'Form 2B',
            'capacity' => 30,
        ]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $grade->id,
            'class_id' => $from->id,
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $from->id,
            'academic_year' => (string) now()->year,
            'enrolled_at' => now()->subMonth(),
            'status' => 'active',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/students/{$student->id}/placements", [
                'class_id' => $to->id,
                'academic_year' => (string) now()->year,
                'reason' => 'capacity_balancing',
            ])
            ->assertOk()
            ->assertJsonPath('data.class_id', $to->id);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $from->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $to->id,
            'status' => 'active',
            'reason' => 'capacity_balancing',
        ]);
    }

    public function test_capacity_blocks_overfill(): void
    {
        $auth = $this->createAuthenticatedUser();
        $grade = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $class = ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $grade->id,
            'capacity' => 1,
        ]);
        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);
        $moving = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/students/{$moving->id}/placements", [
                'class_id' => $class->id,
                'academic_year' => (string) now()->year,
            ])
            ->assertStatus(422);
    }

    public function test_lifecycle_transition_withdraw_issues_tc_and_records_event(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
            'full_name' => 'Leave Me',
            'student_number' => 'TST20260001',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/students/{$student->id}/lifecycle/transition", [
                'action' => 'withdraw',
                'reason' => 'Relocating',
                'issue_tc' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.student.status', 'withdrawn');

        $this->assertDatabaseHas('student_status_events', [
            'student_id' => $student->id,
            'action' => 'withdraw',
            'to_status' => 'withdrawn',
        ]);
        $this->assertNotNull($response->json('data.certificate.id'));

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->get("/api/v1/students/{$student->id}/transfer-certificate")
            ->assertOk();
    }

    public function test_lifecycle_show_includes_placement(): void
    {
        $auth = $this->createAuthenticatedUser();
        $stream = Stream::create([
            'school_id' => $auth['school']->id,
            'name' => 'Arts',
            'is_active' => true,
        ]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'stream_id' => $stream->id,
            'status' => 'active',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/students/{$student->id}/lifecycle")
            ->assertOk()
            ->assertJsonPath('data.placement.stream.id', $stream->id)
            ->assertJsonStructure(['data' => ['placement', 'admission', 'status_events', 'counselling', 'medical']]);
    }

    public function test_transcript_download(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'full_name' => 'Transcript Student',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->get("/api/v1/students/{$student->id}/transcript")
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8');
    }
}
