<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::all() as $school) {
            $classes = ClassModel::where('school_id', $school->id)->get();
            $teachers = Teacher::where('school_id', $school->id)->get();
            if ($classes->isEmpty() || $teachers->isEmpty()) {
                continue;
            }
            foreach (ZimbabweData::SUBJECTS as $idx => $name) {
                $classModel = $classes[$idx % $classes->count()];
                $teacher = $teachers[$idx % $teachers->count()];
                Subject::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $name,
                    ],
                    [
                        'code' => strtoupper(substr($name, 0, 3)) . ($idx + 1),
                        'class_id' => $classModel->id,
                        'teacher_id' => $teacher->id,
                    ]
                );
            }
        }
    }
}
