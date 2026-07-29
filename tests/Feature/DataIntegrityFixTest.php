<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Transaction;
use App\Services\GuardianService;
use Tests\TestCase;

class DataIntegrityFixTest extends TestCase
{
    public function test_student_detail_invoice_debits_student_balance(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'finance');
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'balance' => 0,
        ]);

        $response = $this->withToken($auth['token'])->postJson("/api/v1/students/{$student->id}/invoices", [
            'amount' => 250.00,
            'description' => 'Sports levy',
            'dueDate' => now()->addMonth()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', '250.00');

        $this->assertSame(250.0, (float) $student->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'student_id' => $student->id,
            'invoice_id' => $response->json('data.id'),
            'type' => 'fee_applied',
            'debit' => 250,
        ]);
    }

    public function test_invoice_amount_update_syncs_student_balance(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'finance');
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'balance' => 800,
        ]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 1000,
            'amount_paid' => 200,
            'balance' => 800,
            'status' => 'partial',
            'currency' => $auth['school']->currency_default ?? 'USD',
        ]);

        $this->withToken($auth['token'])->putJson("/api/v1/invoices/{$invoice->id}", [
            'amount' => 1200.00,
        ])->assertOk()
            ->assertJsonPath('data.amount', '1200.00')
            ->assertJsonPath('data.balance', '1000.00');

        $this->assertSame(1000.0, (float) $student->fresh()->balance);
        $this->assertTrue(
            Transaction::query()
                ->where('invoice_id', $invoice->id)
                ->where('type', 'fee_adjustment_debit')
                ->exists()
        );
    }

    public function test_student_class_edit_updates_enrollment(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        $from = ClassModel::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Form 1A']);
        $to = ClassModel::factory()->create(['school_id' => $auth['school']->id, 'name' => 'Form 1B']);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $from->id,
            'class' => $from->name,
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $from->id,
            'academic_year' => (string) date('Y'),
            'enrolled_at' => now()->toDateString(),
            'status' => 'active',
            'reason' => 'admission',
        ]);

        $this->withToken($auth['token'])->putJson("/api/v1/students/{$student->id}", [
            'class_id' => $to->id,
        ])->assertOk();

        $student->refresh();
        $this->assertSame($to->id, (int) $student->class_id);
        $this->assertSame($to->name, $student->class);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $to->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $from->id,
            'status' => 'completed',
        ]);
    }

    public function test_guardian_phone_collision_does_not_steal_wrong_parent(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        $existing = Guardian::factory()->create([
            'school_id' => $auth['school']->id,
            'email' => 'parent.a@school.test',
            'phone' => '0771234567',
            'first_name' => 'Alice',
            'last_name' => 'One',
        ]);

        $service = app(GuardianService::class);
        $matched = $service->createOrFindGuardian([
            'first_name' => 'Bob',
            'last_name' => 'Two',
            'email' => 'parent.b@school.test',
            'phone' => '0771234567',
            'relationship' => 'father',
        ], $auth['school']->id);

        $this->assertNotSame($existing->id, $matched->id);
        $this->assertSame('parent.b@school.test', $matched->email);
    }
}
