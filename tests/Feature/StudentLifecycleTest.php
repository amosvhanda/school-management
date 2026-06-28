<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\EnrollmentApplication;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\HolidayProgram;
use App\Models\InventoryItem;
use App\Models\Student;
use Tests\TestCase;

class StudentLifecycleTest extends TestCase
{
    public function test_enrollment_approval_creates_student_ledger_and_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $gradeLevel = GradeLevel::factory()->create([
            'school_id' => $auth['school']->id,
            'name' => 'Form 1',
            'order' => 1,
        ]);
        $class = ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Form 1A',
        ]);
        FeeStructure::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'category' => 'Tuition',
            'amount' => 500,
        ]);

        $application = EnrollmentApplication::create([
            'school_id' => $auth['school']->id,
            'first_name' => 'Tendai',
            'surname' => 'Moyo',
            'date_of_birth' => '2012-01-15',
            'gender' => 'male',
            'phone' => '+263771234567',
            'address' => '123 Main St',
            'grade_applying_for' => 'Form 1',
            'academic_year' => (string) now()->year,
            'guardian_first_name' => 'John',
            'guardian_surname' => 'Moyo',
            'guardian_phone' => '+263771234568',
            'guardian_relationship' => 'parent',
            'guardian_address' => '123 Main St',
            'emergency_contact' => 'Jane Moyo',
            'emergency_phone' => '+263771234569',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/enrollment-applications/{$application->id}/approve", [
            'class_id' => $class->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.student.student_number', fn ($v) => ! empty($v));

        $studentId = $response->json('data.student.id');
        $this->assertDatabaseHas('transactions', ['student_id' => $studentId, 'type' => 'fee_applied']);
        $this->assertGreaterThan(0, Student::find($studentId)->balance);
    }

    public function test_year_end_promotion_and_repetition_update_enrollments_and_ledger(): void
    {
        $auth = $this->createAuthenticatedUser();
        $form1 = GradeLevel::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Form 1', 'code' => 'F1', 'order' => 1]);
        $form2 = GradeLevel::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Form 2', 'code' => 'F2', 'order' => 2]);
        $class1 = ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $form1->id,
            'name' => 'Form 1A',
        ]);
        $class2 = ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $form2->id,
            'name' => 'Form 2A',
        ]);

        FeeStructure::factory()->create(['school_id' => $auth['school']->id, 'class_id' => $class2->id, 'amount' => 600]);

        $passing = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $form1->id,
            'class_id' => $class1->id,
            'balance' => 0,
        ]);
        $failing = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $form1->id,
            'class_id' => $class1->id,
            'balance' => 0,
        ]);

        Grade::factory()->create(['school_id' => $auth['school']->id, 'student_id' => $passing->id, 'score' => 80, 'total' => 100, 'year' => now()->year]);
        Grade::factory()->create(['school_id' => $auth['school']->id, 'student_id' => $failing->id, 'score' => 30, 'total' => 100, 'year' => now()->year]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/students/bulk/promote', [
            'studentIds' => [$passing->id, $failing->id],
            'academic_year' => (string) now()->year,
            'next_academic_year' => (string) (now()->year + 1),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.promoted', 1)
            ->assertJsonPath('data.repeated', 1);

        $this->assertEquals($form2->id, $passing->fresh()->grade_level_id);
        $this->assertEquals(1, $failing->fresh()->repetition_count);
        $this->assertDatabaseHas('enrollments', ['student_id' => $failing->id, 'status' => 'repeating']);
    }

    public function test_inventory_sale_decreases_stock_and_bills_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id, 'balance' => 0]);
        $item = InventoryItem::create([
            'school_id' => $auth['school']->id,
            'name' => 'School Shirt',
            'type' => 'uniform',
            'size' => 'M',
            'unit_price' => 25,
            'stock_quantity' => 10,
            'billing_mode' => 'direct_sale',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/inventory/sales', [
            'items' => [['item_id' => $item->id, 'quantity' => 2]],
            'payment_method' => 'student_account',
            'student_id' => $student->id,
        ]);

        $response->assertCreated();
        $this->assertEquals(8, $item->fresh()->stock_quantity);
        $this->assertGreaterThan(0, $student->fresh()->balance);
    }

    public function test_holiday_program_enrollment_invoices_only_enrolled_students(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id, 'balance' => 0]);

        $program = HolidayProgram::create([
            'school_id' => $auth['school']->id,
            'name' => 'April Holiday Maths',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeeks(2),
            'fee_amount' => 100,
            'is_active' => true,
        ]);

        $enroll = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/holiday-programs/{$program->id}/enroll", [
            'student_id' => $student->id,
        ]);

        $enroll->assertCreated();
        $this->assertEquals(100, $student->fresh()->balance);

        $attendance = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/holiday-programs/{$program->id}/attendance", [
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $attendance->assertOk();
    }
}
