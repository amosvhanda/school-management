<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Keep names unique per school (unique index on school_id + name).
        // A fixed subject list collides when a test creates 2+ subjects.
        return [
            'name' => fake()->randomElement([
                'Mathematics', 'English', 'Science', 'History',
                'Geography', 'Physics', 'Chemistry', 'Biology',
            ]).' '.fake()->unique()->bothify('##??'),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'school_id' => \App\Models\School::factory(),
        ];
    }
}
