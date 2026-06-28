<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Term;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = date('Y');
        $academicYear = "{$currentYear}-" . ($currentYear + 1);
        
        $terms = [
            [
                'name' => 'Term 1',
                'order' => 1,
                'start_date' => "{$currentYear}-01-15",
                'end_date' => "{$currentYear}-04-15",
                'is_current' => true,
            ],
            [
                'name' => 'Term 2',
                'order' => 2,
                'start_date' => "{$currentYear}-05-01",
                'end_date' => "{$currentYear}-08-15",
                'is_current' => false,
            ],
            [
                'name' => 'Term 3',
                'order' => 3,
                'start_date' => "{$currentYear}-09-01",
                'end_date' => "{$currentYear}-12-15",
                'is_current' => false,
            ],
        ];

        foreach (School::all() as $school) {
            foreach ($terms as $term) {
                Term::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'academic_year' => $academicYear,
                        'name' => $term['name'],
                    ],
                    [
                        'start_date' => $term['start_date'],
                        'end_date' => $term['end_date'],
                        'is_current' => $term['is_current'],
                        'is_active' => true,
                        'order' => $term['order'],
                        'description' => "{$term['name']} of {$academicYear}",
                    ]
                );
            }
        }
    }
}
