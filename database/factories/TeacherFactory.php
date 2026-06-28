<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => 'EMP' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+263' . fake()->numberBetween(700000000, 799999999),
            'address' => fake()->address(),
            'subject' => fake()->randomElement(['Mathematics', 'English', 'Science', 'History']),
            'department' => fake()->randomElement(['Science', 'Arts', 'Languages']),
            'qualification' => fake()->randomElement(['BSc', 'MSc', 'PhD']),
            'joining_date' => fake()->date('Y-m-d', '-5 years'),
            'status' => 'active',
            'school_id' => \App\Models\School::factory(),
            'base_salary' => 1500.00,
            'salary_currency' => 'USD',
        ];
    }
}
