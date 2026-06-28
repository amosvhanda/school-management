<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    public function test_get_transactions_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/transactions');

        $response->assertStatus(200);
    }

    public function test_get_transactions_summary(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/transactions/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_debit_usd',
                    'total_credit_usd',
                    'net_balance_usd',
                ],
            ]);
    }
}
