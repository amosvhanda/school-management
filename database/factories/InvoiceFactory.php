<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV' . fake()->unique()->numberBetween(100000, 999999),
            'student_id' => \App\Models\Student::factory(),
            'fee_structure_id' => \App\Models\FeeStructure::factory(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 500, 5000),
            'amount_paid' => 0,
            'balance' => fake()->randomFloat(2, 500, 5000),
            'currency' => 'USD',
            'due_date' => fake()->date('Y-m-d', '+1 month'),
            'status' => 'pending',
            'school_id' => \App\Models\School::factory(),
        ];
    }
}
