<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassModel>
 */
class ClassModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Form 1A', 'Form 1B', 'Form 2A', 'Form 2B', 'Form 3A', 'Form 3B']),
            'form' => fake()->randomElement(['Form 1', 'Form 2', 'Form 3', 'Form 4']),
            'capacity' => fake()->numberBetween(25, 40),
            'current_enrollment' => fake()->numberBetween(20, 35),
            'status' => 'active',
            'school_id' => School::factory(),
        ];
    }
}
