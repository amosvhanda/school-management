<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
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
            'class_id' => \App\Models\ClassModel::factory(),
            'date' => fake()->date('Y-m-d'),
            'status' => fake()->randomElement(['present', 'absent', 'late', 'excused']),
            'time_in' => fake()->dateTimeBetween('07:00', '08:00'),
            'school_id' => \App\Models\School::factory(),
        ];
    }
}
