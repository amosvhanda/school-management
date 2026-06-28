<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => \App\Models\Student::factory(),
            'invoice_id' => \App\Models\Invoice::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'USD',
            'method' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_money', 'card']),
            'reference' => 'REF' . fake()->unique()->numberBetween(100000, 999999),
            'status' => 'completed',
            'date' => fake()->date('Y-m-d'),
            'school_id' => \App\Models\School::factory(),
        ];
    }
}
