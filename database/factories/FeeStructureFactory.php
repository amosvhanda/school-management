<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeeStructure>
 */
class FeeStructureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_name' => fake()->randomElement(['Form 1', 'Form 2', 'Form 3', 'Form 4']),
            'category' => fake()->randomElement(['tuition', 'boarding', 'library', 'sports', 'examination']),
            'amount' => fake()->randomFloat(2, 500, 5000),
            'currency' => 'USD',
            'school_id' => \App\Models\School::factory(),
        ];
    }
}
