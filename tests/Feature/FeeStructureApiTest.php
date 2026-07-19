<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\FeeStructure;
use Tests\TestCase;

class FeeStructureApiTest extends TestCase
{
    public function test_get_fee_structures_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $feeStructure = FeeStructure::factory()->create(['school_id' => $auth['school']->id]);
        $otherFeeStructure = FeeStructure::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/fee-structures?all=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $feeStructure->id)
            ->assertJsonPath('data.0.school_id', $auth['school']->id);

        $this->assertNotContains($otherFeeStructure->id, array_column($response->json('data'), 'id'));
    }

    public function test_create_fee_structure(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/fee-structures', [
            'class' => $class->name,
            'category' => 'tuition',
            'amount' => 5000.00,
            'currency' => 'USD',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'category', 'amount', 'class_name', 'school_id'],
                'message',
            ])
            ->assertJsonPath('data.category', 'tuition')
            ->assertJsonPath('data.class_name', $class->name)
            ->assertJsonPath('data.school_id', $auth['school']->id);

        $feeStructureId = $response->json('data.id');

        $this->assertDatabaseHas('fee_structures', [
            'id' => $feeStructureId,
            'class_name' => $class->name,
            'category' => 'tuition',
            'amount' => 5000,
            'currency' => 'USD',
            'school_id' => $auth['school']->id,
        ]);

        $this->assertDatabaseHas('fee_categories', [
            'school_id' => $auth['school']->id,
            'name' => 'tuition',
        ]);
        $this->assertNotNull($response->json('data.fee_category_id'));
    }

    public function test_update_fee_structure(): void
    {
        $auth = $this->createAuthenticatedUser();
        $feeStructure = FeeStructure::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/fee-structures/{$feeStructure->id}", [
            'amount' => 5500.00,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $feeStructure->id)
            ->assertJsonPath('data.amount', '5500.00')
            ->assertJsonPath('message', 'Fee structure updated successfully');

        $this->assertDatabaseHas('fee_structures', [
            'id' => $feeStructure->id,
            'amount' => 5500,
        ]);
    }

    public function test_delete_fee_structure(): void
    {
        $auth = $this->createAuthenticatedUser();
        $feeStructure = FeeStructure::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/fee-structures/{$feeStructure->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Fee structure deleted successfully');

        $this->assertDatabaseMissing('fee_structures', [
            'id' => $feeStructure->id,
        ]);
    }

    public function test_create_fee_structure_with_category_relation(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $category = \App\Models\FeeCategory::create([
            'school_id' => $auth['school']->id,
            'name' => 'Tuition Fees',
            'description' => 'Core tuition',
            'is_active' => true,
            'order' => 1,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/fee-structures', [
            'class_id' => $class->id,
            'fee_category_id' => $category->id,
            'amount' => 1200.00,
            'currency' => 'USD',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.fee_category_id', $category->id)
            ->assertJsonPath('data.category', 'Tuition Fees')
            ->assertJsonPath('data.class_id', $class->id)
            ->assertJsonPath('data.class_name', $class->name)
            ->assertJsonPath('data.fee_category.name', 'Tuition Fees');
    }

    public function test_filter_fee_structures_by_category(): void
    {
        $auth = $this->createAuthenticatedUser();
        $category = \App\Models\FeeCategory::create([
            'school_id' => $auth['school']->id,
            'name' => 'Sports Levy',
            'is_active' => true,
            'order' => 0,
        ]);
        $other = \App\Models\FeeCategory::create([
            'school_id' => $auth['school']->id,
            'name' => 'Library',
            'is_active' => true,
            'order' => 1,
        ]);

        $match = FeeStructure::factory()->create([
            'school_id' => $auth['school']->id,
            'fee_category_id' => $category->id,
            'category' => $category->name,
        ]);
        FeeStructure::factory()->create([
            'school_id' => $auth['school']->id,
            'fee_category_id' => $other->id,
            'category' => $other->name,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/fee-structures?fee_category_id='.$category->id.'&all=true');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($match->id, $response->json('data.0.id'));
    }

    public function test_fee_categories_include_structure_count(): void
    {
        $auth = $this->createAuthenticatedUser();
        $category = \App\Models\FeeCategory::create([
            'school_id' => $auth['school']->id,
            'name' => 'Exam Fees',
            'is_active' => true,
            'order' => 0,
        ]);
        FeeStructure::factory()->count(2)->create([
            'school_id' => $auth['school']->id,
            'fee_category_id' => $category->id,
            'category' => $category->name,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/fee-categories');

        $response->assertOk();
        $row = collect($response->json('data'))->firstWhere('id', $category->id);
        $this->assertNotNull($row);
        $this->assertSame(2, $row['fee_structures_count']);
    }
}
