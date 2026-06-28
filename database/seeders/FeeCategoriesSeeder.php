<?php

namespace Database\Seeders;

use App\Models\FeeCategory;
use App\Models\School;
use Illuminate\Database\Seeder;

class FeeCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Tuition Fees', 'order' => 1],
            ['name' => 'Development Levy', 'order' => 2],
            ['name' => 'Exam Fees', 'order' => 3],
            ['name' => 'Laboratory Fees', 'order' => 4],
            ['name' => 'Library Fees', 'order' => 5],
            ['name' => 'Computer Fees', 'order' => 6],
            ['name' => 'Sports Levy', 'order' => 7],
            ['name' => 'General Purpose', 'order' => 8],
            ['name' => 'Building Fund', 'order' => 9],
            ['name' => 'School Trip', 'order' => 10],
            ['name' => 'Uniform', 'order' => 11],
            ['name' => 'Textbooks', 'order' => 12],
            ['name' => 'Stationery', 'order' => 13],
            ['name' => 'Transport', 'order' => 14],
            ['name' => 'Boarding Fees', 'order' => 15],
        ];

        foreach (School::all() as $school) {
            foreach ($categories as $category) {
                FeeCategory::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $category['name'],
                    ],
                    [
                        'order' => $category['order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
