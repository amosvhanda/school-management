<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Mathematics',
            'Sciences',
            'Languages',
            'Humanities',
            'Commerce',
        ]);

        return [
            'school_id' => School::factory(),
            'name' => $name,
            'code' => strtoupper(substr($name, 0, 3)),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
