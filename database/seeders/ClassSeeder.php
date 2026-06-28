<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Teacher;
use Database\Seeders\Helpers\SeederRelations;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::all() as $school) {
            $teachers = Teacher::where('school_id', $school->id)->get();
            if ($teachers->isEmpty()) {
                continue;
            }

            $gradeLevels = GradeLevel::query()
                ->where('school_id', $school->id)
                ->get()
                ->keyBy('name');

            foreach (ZimbabweData::CLASS_NAMES as $i => $name) {
                $form = preg_replace('/\s*[AB]?\s*$/', '', $name);
                $gradeLevelName = ZimbabweData::gradeLevelNameForClass($name);
                $gradeLevel = $gradeLevels->get($gradeLevelName);

                ClassModel::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $name,
                    ],
                    [
                        'form' => $form,
                        'school' => $school->name,
                        'capacity' => in_array($name, ['Lower 6', 'Upper 6'], true) ? 30 : 40,
                        'current_enrollment' => 0,
                        'teacher_id' => $teachers[$i % $teachers->count()]->id,
                        'grade_level_id' => $gradeLevel?->id,
                        'status' => 'active',
                    ]
                );
            }

            SeederRelations::syncClassGradeLevels($school);
        }
    }
}
