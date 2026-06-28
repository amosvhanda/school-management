<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FeeStructure;
use App\Models\ClassModel;

class FeeStructureApiTest extends TestCase
{
    public function test_get_fee_structures_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/fee-structures');

        $response->assertStatus(200);
    }

    public function test_create_fee_structure(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/fee-structures', [
            'class' => $class->name,
            'category' => 'tuition',
            'amount' => 5000.00,
            'currency' => 'USD',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'category', 'amount'],
                'message',
            ]);
    }

    public function test_update_fee_structure(): void
    {
        $auth = $this->createAuthenticatedUser();
        $feeStructure = FeeStructure::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/v1/fee-structures/{$feeStructure->id}", [
            'amount' => 5500.00,
        ]);

        $response->assertStatus(200);
    }

    public function test_delete_fee_structure(): void
    {
        $auth = $this->createAuthenticatedUser();
        $feeStructure = FeeStructure::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->deleteJson("/api/v1/fee-structures/{$feeStructure->id}");

        $response->assertStatus(200);
    }
}
