<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GradeLevel>
 */
class GradeLevelFactory extends Factory
{
    public function definition(): array
    {
        $order = fake()->unique()->numberBetween(1, 99);

        return [
            'school_id' => School::factory(),
            'name' => "Form {$order}",
            'code' => "F{$order}",
            'order' => $order,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
