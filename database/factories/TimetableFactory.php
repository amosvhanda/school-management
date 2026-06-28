<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timetable>
 */
class TimetableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => \App\Models\ClassModel::factory(),
            'teacher_id' => \App\Models\Teacher::factory(),
            'subject_id' => \App\Models\Subject::factory(),
            'subject' => fake()->randomElement(['Mathematics', 'English', 'Science']),
            'day' => fake()->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
            'start_time' => fake()->dateTimeBetween('08:00', '14:00'),
            'end_time' => fake()->dateTimeBetween('09:00', '15:00'),
            'room' => 'Room ' . fake()->numberBetween(1, 20),
            'school_id' => \App\Models\School::factory(),
        ];
    }
}
