<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Transaction;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    public function test_get_transactions_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $transaction = Transaction::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'type' => 'payment',
            'category' => 'student',
            'description' => 'Payment received',
            'reference' => 'TXN-001',
            'debit' => 0,
            'credit' => 150,
            'balance' => 350,
            'currency' => 'USD',
            'status' => 'completed',
        ]);

        $otherStudent = Student::factory()->create();
        Transaction::withoutGlobalScopes()->create([
            'school_id' => $otherStudent->school_id,
            'student_id' => $otherStudent->id,
            'type' => 'fee_applied',
            'category' => 'student',
            'description' => 'Other school fee',
            'reference' => 'TXN-OTHER',
            'debit' => 200,
            'credit' => 0,
            'balance' => 200,
            'currency' => 'USD',
            'status' => 'completed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/transactions');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $transaction->id)
            ->assertJsonPath('data.0.student_id', $student->id)
            ->assertJsonPath('data.0.reference', 'TXN-001');
    }

    public function test_get_transactions_summary(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        Transaction::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'type' => 'fee_applied',
            'category' => 'student',
            'description' => 'Invoice raised',
            'reference' => 'TXN-002',
            'debit' => 500,
            'credit' => 0,
            'balance' => 500,
            'currency' => 'USD',
            'status' => 'completed',
        ]);
        Transaction::create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'type' => 'payment',
            'category' => 'student',
            'description' => 'Payment received',
            'reference' => 'TXN-003',
            'debit' => 0,
            'credit' => 200,
            'balance' => 300,
            'currency' => 'USD',
            'status' => 'completed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/transactions/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_debit_usd',
                    'total_credit_usd',
                    'net_balance_usd',
                ],
            ])
            ->assertJsonPath('data.total_debit_usd', 500)
            ->assertJsonPath('data.total_credit_usd', 200)
            ->assertJsonPath('data.net_balance_usd', 300)
            ->assertJsonPath('data.total_transactions', 2);
    }
}
