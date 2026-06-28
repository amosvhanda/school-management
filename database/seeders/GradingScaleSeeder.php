<?php

namespace Database\Seeders;

use App\Models\GradingScale;
use App\Models\School;
use Illuminate\Database\Seeder;

class GradingScaleSeeder extends Seeder
{
    public function run(): void
    {
        $scales = [
            ['grade' => 'A+', 'min_score' => 90, 'max_score' => 100, 'description' => 'Excellent', 'order' => 1],
            ['grade' => 'A', 'min_score' => 80, 'max_score' => 89.99, 'description' => 'Very Good', 'order' => 2],
            ['grade' => 'B+', 'min_score' => 75, 'max_score' => 79.99, 'description' => 'Good', 'order' => 3],
            ['grade' => 'B', 'min_score' => 70, 'max_score' => 74.99, 'description' => 'Satisfactory', 'order' => 4],
            ['grade' => 'C+', 'min_score' => 65, 'max_score' => 69.99, 'description' => 'Fair', 'order' => 5],
            ['grade' => 'C', 'min_score' => 60, 'max_score' => 64.99, 'description' => 'Average', 'order' => 6],
            ['grade' => 'D', 'min_score' => 50, 'max_score' => 59.99, 'description' => 'Below Average', 'order' => 7],
            ['grade' => 'E', 'min_score' => 40, 'max_score' => 49.99, 'description' => 'Poor', 'order' => 8],
            ['grade' => 'F', 'min_score' => 0, 'max_score' => 39.99, 'description' => 'Fail', 'order' => 9],
        ];

        foreach (School::all() as $school) {
            foreach ($scales as $scale) {
                GradingScale::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'grade' => $scale['grade'],
                    ],
                    [
                        'min_score' => $scale['min_score'],
                        'max_score' => $scale['max_score'],
                        'description' => $scale['description'],
                        'order' => $scale['order'],
                    ]
                );
            }
        }
    }
}
