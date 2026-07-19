<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Teacher;
use App\Models\Transaction;
use Tests\TestCase;

class CashFlowApiTest extends TestCase
{
    public function test_cash_flow_balances_money_in_and_out(): void
    {
        $auth = $this->createAuthenticatedUser();
        $currency = $auth['school']->currency_default ?? 'USD';
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'amount' => 1000,
            'currency' => $currency,
            'status' => 'completed',
            'date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        Transaction::create([
            'school_id' => $auth['school']->id,
            'type' => 'payment',
            'category' => 'student',
            'description' => 'Fee payment',
            'debit' => 0,
            'credit' => 1000,
            'balance' => 0,
            'currency' => $currency,
            'status' => 'completed',
            'payment_method' => 'cash',
            'created_by' => $auth['user']->id,
        ]);

        $payroll = Payroll::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'month' => (int) now()->format('n'),
            'year' => (int) now()->format('Y'),
            'base_salary' => 400,
            'allowances_total' => 0,
            'gross_salary' => 400,
            'deductions_total' => 0,
            'net_salary' => 400,
            'amount_paid' => 400,
            'currency' => $currency,
            'status' => 'paid',
        ]);

        Transaction::create([
            'school_id' => $auth['school']->id,
            'payroll_id' => $payroll->id,
            'type' => 'expense',
            'category' => 'payroll',
            'description' => 'Payroll payment',
            'debit' => 400,
            'credit' => 0,
            'balance' => -400,
            'currency' => $currency,
            'status' => 'completed',
            'payment_method' => 'bank_transfer',
            'created_by' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/finance/cash-flow?period=monthly&currency='.$currency);

        $response->assertOk()
            ->assertJsonPath('data.money_in.total', 1000)
            ->assertJsonPath('data.money_out.total', 400)
            ->assertJsonPath('data.net_cash', 600)
            ->assertJsonPath('data.balance_check.is_balanced', true);

        $net = (float) $response->json('data.money_in.total') - (float) $response->json('data.money_out.total');
        $this->assertEqualsWithDelta((float) $response->json('data.net_cash'), $net, 0.001);
    }
}
