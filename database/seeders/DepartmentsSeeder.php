<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\School;
use Illuminate\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Sciences', 'code' => 'SCI', 'description' => 'Mathematics, Physics, Chemistry, Biology, Physical Science'],
            ['name' => 'Languages', 'code' => 'LANG', 'description' => 'English, Shona, Ndebele'],
            ['name' => 'Arts', 'code' => 'ARTS', 'description' => 'History, Geography, Art, Music'],
            ['name' => 'Commerce', 'code' => 'COMM', 'description' => 'Accounting, Business Studies, Economics'],
            ['name' => 'Technical', 'code' => 'TECH', 'description' => 'Computer Science, Physical Education'],
        ];

        foreach (School::all() as $school) {
            foreach ($departments as $dept) {
                Department::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $dept['name'],
                    ],
                    [
                        'code' => $dept['code'],
                        'description' => $dept['description'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
