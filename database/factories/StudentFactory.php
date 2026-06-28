<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_number' => 'STU' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->date('Y-m-d', '-10 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+263' . fake()->numberBetween(700000000, 799999999),
            'address' => fake()->address(),
            'status' => 'active',
            'balance' => 0,
            'currency' => 'USD',
            'school_id' => \App\Models\School::factory(),
        ];
    }
}
