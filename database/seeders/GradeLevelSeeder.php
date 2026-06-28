<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\School;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $gradeLevels = [
            ['name' => 'Grade 7', 'code' => 'G7', 'order' => 1],
            ['name' => 'Form 1', 'code' => 'F1', 'order' => 2],
            ['name' => 'Form 2', 'code' => 'F2', 'order' => 3],
            ['name' => 'Form 3', 'code' => 'F3', 'order' => 4],
            ['name' => 'Form 4', 'code' => 'F4', 'order' => 5],
            ['name' => 'Lower 6', 'code' => 'L6', 'order' => 6],
            ['name' => 'Upper 6', 'code' => 'U6', 'order' => 7],
        ];

        foreach (School::all() as $school) {
            foreach ($gradeLevels as $level) {
                GradeLevel::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $level['name'],
                    ],
                    [
                        'code' => $level['code'],
                        'order' => $level['order'],
                        'description' => "{$level['name']} level for {$school->name}",
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
