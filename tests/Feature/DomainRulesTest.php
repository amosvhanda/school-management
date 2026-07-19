<?php

namespace Tests\Feature;

use App\Exceptions\DomainException;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Services\Domain\SchoolDomainRules;
use App\Services\StudentLifecycleActionService;
use Tests\TestCase;

class DomainRulesTest extends TestCase
{
    public function test_transfer_blocked_when_fees_outstanding(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
            'balance' => 120.50,
            'currency' => 'USD',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('fees are outstanding');

        app(StudentLifecycleActionService::class)->transition($student, [
            'action' => 'transfer_out',
            'reason' => 'Moving schools',
            'to_school_id' => $auth['school']->id,
        ], $auth['user']);
    }

    public function test_admission_number_duplicate_rejected(): void
    {
        $auth = $this->createAuthenticatedUser();
        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'student_number' => 'ADM-1001',
        ]);

        try {
            app(SchoolDomainRules::class)->assertAdmissionNumberAvailable(
                $auth['school']->id,
                'ADM-1001',
            );
            $this->fail('Expected DomainException');
        } catch (DomainException $e) {
            $this->assertSame('admission_number_exists', $e->errorCode);
        }
    }

    public function test_subject_not_in_grade_package_rejected(): void
    {
        $auth = $this->createAuthenticatedUser();
        $grade = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $allowed = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $other = Subject::factory()->create(['school_id' => $auth['school']->id]);

        GradeLevelSubject::create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $grade->id,
            'subject_id' => $allowed->id,
            'is_core' => true,
        ]);

        try {
            app(SchoolDomainRules::class)->assertSubjectAvailableForGrade(
                $auth['school']->id,
                $grade->id,
                $other->id,
            );
            $this->fail('Expected DomainException');
        } catch (DomainException $e) {
            $this->assertSame('subject_not_in_grade', $e->errorCode);
        }
    }

    public function test_transport_route_capacity_enforced(): void
    {
        $auth = $this->createAuthenticatedUser();
        $vehicle = Vehicle::create([
            'school_id' => $auth['school']->id,
            'registration_number' => 'ABC-123',
            'capacity' => 1,
            'status' => 'active',
        ]);
        $route = TransportRoute::create([
            'school_id' => $auth['school']->id,
            'name' => 'Route A',
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);
        $studentA = Student::factory()->create(['school_id' => $auth['school']->id, 'status' => 'active']);
        $studentB = Student::factory()->create(['school_id' => $auth['school']->id, 'status' => 'active']);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/transport/allocations', [
                'student_id' => $studentA->id,
                'route_id' => $route->id,
            ])
            ->assertCreated();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/transport/allocations', [
                'student_id' => $studentB->id,
                'route_id' => $route->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'transport_route_full');
    }

    public function test_payment_exceeds_balance_returns_domain_error(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 100,
            'balance' => 50,
            'status' => 'partial',
            'currency' => $auth['school']->currency_default ?? 'USD',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'amount' => 80,
                'method' => 'cash',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'payment_exceeds_balance');
    }

    public function test_multiple_active_enrollments_detected(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id, 'status' => 'active']);
        $classA = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $classB = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        Enrollment::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $classA->id,
            'academic_year' => '2026',
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $classB->id,
            'academic_year' => '2026',
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        try {
            app(SchoolDomainRules::class)->assertSingleActiveEnrollment($student);
            $this->fail('Expected DomainException');
        } catch (DomainException $e) {
            $this->assertSame('multiple_active_classes', $e->errorCode);
        }
    }
}
