<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Term>
 */
class TermFactory extends Factory
{
    public function definition(): array
    {
        $year = now()->year;

        return [
            'school_id' => School::factory(),
            'name' => fake()->randomElement(['Term 1', 'Term 2', 'Term 3']),
            'academic_year' => "{$year}-".($year + 1),
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_current' => false,
            'is_active' => true,
            'order' => fake()->numberBetween(1, 3),
        ];
    }
}
