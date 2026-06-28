<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Term;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'term_id' => Term::factory(),
            'grade_level_id' => GradeLevel::factory(),
            'subject_id' => Subject::factory(),
            'name' => fake()->randomElement(['Mid-Term Exam', 'End of Term Exam', 'Final Exam']),
            'description' => fake()->optional()->sentence(),
            'exam_date' => fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'total_marks' => 100,
            'passing_marks' => 50,
            'academic_year' => now()->year.'-'.(now()->year + 1),
            'is_published' => false,
        ];
    }
}
