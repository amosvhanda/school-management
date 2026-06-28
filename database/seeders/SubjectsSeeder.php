<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\School;
use Illuminate\Database\Seeder;

class SubjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MAT', 'order' => 1],
            ['name' => 'English', 'code' => 'ENG', 'order' => 2],
            ['name' => 'Physical Science', 'code' => 'PSC', 'order' => 3],
            ['name' => 'Biology', 'code' => 'BIO', 'order' => 4],
            ['name' => 'Chemistry', 'code' => 'CHE', 'order' => 5],
            ['name' => 'Physics', 'code' => 'PHY', 'order' => 6],
            ['name' => 'History', 'code' => 'HIS', 'order' => 7],
            ['name' => 'Geography', 'code' => 'GEO', 'order' => 8],
            ['name' => 'Shona', 'code' => 'SHO', 'order' => 9],
            ['name' => 'Ndebele', 'code' => 'NDE', 'order' => 10],
            ['name' => 'Art', 'code' => 'ART', 'order' => 11],
            ['name' => 'Music', 'code' => 'MUS', 'order' => 12],
            ['name' => 'Physical Education', 'code' => 'PED', 'order' => 13],
            ['name' => 'Computer Science', 'code' => 'COM', 'order' => 14],
            ['name' => 'Accounting', 'code' => 'ACC', 'order' => 15],
            ['name' => 'Business Studies', 'code' => 'BUS', 'order' => 16],
            ['name' => 'Economics', 'code' => 'ECO', 'order' => 17],
            ['name' => 'Religious Studies', 'code' => 'REL', 'order' => 18],
        ];

        foreach (School::all() as $school) {
            foreach ($subjects as $subject) {
                Subject::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $subject['name'],
                    ],
                    [
                        'code' => $subject['code'],
                        'order' => $subject['order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
