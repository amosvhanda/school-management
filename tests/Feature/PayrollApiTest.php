<?php

namespace Tests\Feature;

use App\Models\Payroll;
use App\Models\Teacher;
use App\Models\Transaction;
use Tests\TestCase;

class PayrollApiTest extends TestCase
{
    public function test_generate_creates_pending_payslips_for_active_staff(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
            'base_salary' => 1200,
            'allowances' => ['housing' => 100],
            'deductions' => ['pension' => 50],
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/payroll/generate', [
            'month' => 7,
            'year' => 2026,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.created', 1);

        $this->assertDatabaseHas('payroll', [
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 1200,
            'gross_salary' => 1300,
            'net_salary' => 1250,
            'status' => 'pending',
        ]);
    }

    public function test_generate_skips_paid_or_partial_payslips(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
            'base_salary' => 1000,
        ]);

        Payroll::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 1000,
            'allowances' => [],
            'allowances_total' => 0,
            'gross_salary' => 1000,
            'deductions' => [],
            'deductions_total' => 0,
            'net_salary' => 1000,
            'amount_paid' => 1000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/payroll/generate', [
            'month' => 7,
            'year' => 2026,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.created', 0)
            ->assertJsonFragment(['Skipped '.$teacher->name.' — payroll already paid for 7/2026']);

        $this->assertSame(1, Payroll::query()->where('teacher_id', $teacher->id)->count());
    }

    public function test_process_records_payment_and_expense_transaction(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'employee_id' => 'SCH-EMP0001',
        ]);

        $payroll = Payroll::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 1000,
            'allowances' => [],
            'allowances_total' => 0,
            'gross_salary' => 1000,
            'deductions' => [],
            'deductions_total' => 0,
            'net_salary' => 1000,
            'amount_paid' => 0,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/payroll/{$payroll->id}/process", [
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'REF-100',
            'amount_paid' => 400,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.payroll.status', 'partial')
            ->assertJsonPath('data.payroll.amount_paid', '400.00');

        $this->assertDatabaseHas('transactions', [
            'payroll_id' => $payroll->id,
            'type' => 'expense',
            'category' => 'payroll',
            'debit' => 400,
            'payment_method' => 'bank_transfer',
            'reference' => 'REF-100',
            'status' => 'completed',
        ]);

        $this->assertSame(1, Transaction::query()->where('payroll_id', $payroll->id)->count());
    }

    public function test_cannot_edit_paid_payroll(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $payroll = Payroll::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 1000,
            'allowances_total' => 0,
            'gross_salary' => 1000,
            'deductions_total' => 0,
            'net_salary' => 1000,
            'amount_paid' => 1000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/payroll/{$payroll->id}", [
            'base_salary' => 1500,
        ])->assertStatus(422);
    }

    public function test_process_requires_payment_method(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $payroll = Payroll::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 1000,
            'allowances_total' => 0,
            'gross_salary' => 1000,
            'deductions_total' => 0,
            'net_salary' => 1000,
            'amount_paid' => 0,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/payroll/{$payroll->id}/process", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }
}
