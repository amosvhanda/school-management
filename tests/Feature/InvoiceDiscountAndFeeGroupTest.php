<?php

namespace Tests\Feature;

use App\Models\FeeCategory;
use App\Models\FeeDiscount;
use App\Models\FeeGroup;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceDiscountAndFeeGroupTest extends TestCase
{
    use RefreshDatabase;

    private function actingFinanceAdmin(): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'admin',
        ]);
        Sanctum::actingAs($user);

        return compact('school', 'user');
    }

    public function test_invoice_applies_percent_discount_for_student_category(): void
    {
        ['school' => $school] = $this->actingFinanceAdmin();

        $category = StudentCategory::create([
            'school_id' => $school->id,
            'name' => 'Bursary',
            'is_active' => true,
        ]);

        $student = Student::factory()->create([
            'school_id' => $school->id,
            'student_category_id' => $category->id,
            'currency' => 'USD',
            'balance' => 0,
        ]);

        FeeDiscount::create([
            'school_id' => $school->id,
            'name' => 'Bursary 50%',
            'discount_type' => 'percent',
            'value' => 50,
            'student_category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/invoices', [
            'student_id' => $student->id,
            'description' => 'Term fees',
            'amount' => 200,
            'due_date' => now()->addMonth()->toDateString(),
            'apply_discounts' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', '100.00')
            ->assertJsonPath('data.discount_amount', '100.00')
            ->assertJsonPath('data.original_amount', '200.00');

        $this->assertEquals(100.0, (float) $student->fresh()->balance);
    }

    public function test_fee_group_creates_invoices_for_class_structures(): void
    {
        ['school' => $school] = $this->actingFinanceAdmin();

        $class = \App\Models\ClassModel::factory()->create(['school_id' => $school->id]);

        $student = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'class' => $class->name,
            'currency' => 'USD',
            'balance' => 0,
        ]);

        $feeCategory = FeeCategory::create([
            'school_id' => $school->id,
            'name' => 'Tuition',
            'is_active' => true,
        ]);

        FeeStructure::create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'class_name' => $class->name,
            'fee_category_id' => $feeCategory->id,
            'category' => 'Tuition',
            'amount' => 150,
            'currency' => 'USD',
        ]);

        $group = FeeGroup::create([
            'school_id' => $school->id,
            'name' => 'Term bundle',
            'is_active' => true,
        ]);
        $group->categories()->sync([$feeCategory->id]);

        $response = $this->postJson('/api/v1/invoices', [
            'student_id' => $student->id,
            'fee_group_id' => $group->id,
            'due_date' => now()->addMonth()->toDateString(),
            'combine_group' => true,
            'apply_discounts' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('meta.created_count', 1)
            ->assertJsonPath('data.amount', '150.00')
            ->assertJsonPath('data.fee_group_id', $group->id);

        $this->assertDatabaseHas('invoices', [
            'student_id' => $student->id,
            'fee_group_id' => $group->id,
            'amount' => 150,
        ]);
    }
}
