<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
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
            'subject_id' => \App\Models\Subject::factory(),
            'subject' => fake()->randomElement(['Mathematics', 'English', 'Science']),
            'assessment_type' => fake()->randomElement(['test', 'exam', 'assignment', 'quiz']),
            'score' => fake()->randomFloat(2, 0, 100),
            'total' => 100,
            'grade' => fake()->randomElement(['A', 'B', 'C', 'D', 'F']),
            'term' => fake()->randomElement(['Term 1', 'Term 2', 'Term 3']),
            'year' => now()->year,
            'class_id' => \App\Models\ClassModel::factory(),
            'school_id' => \App\Models\School::factory(),
        ];
    }
}
