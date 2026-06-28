<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\School;
use App\Models\Teacher;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = ZimbabweData::SUBJECTS;

        foreach (School::all() as $school) {
            $classes = ClassModel::where('school_id', $school->id)->with('teacher')->get();
            $teachers = Teacher::where('school_id', $school->id)->get();
            if ($classes->isEmpty() || $teachers->isEmpty()) {
                continue;
            }

            foreach ($classes->take(4) as $idx => $class) {
                $teacher = $class->teacher ?? $teachers[$idx % $teachers->count()];
                if (!$teacher) {
                    continue;
                }
                $subject = $subjects[$idx % count($subjects)];
                $title = $subject . ' – Term 1 Assignment ' . ($idx + 1);
                $due = now()->addDays(14)->format('Y-m-d');

                $exists = DB::table('assignments')
                    ->where('school_id', $school->id)
                    ->where('class_id', $class->id)
                    ->where('title', $title)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('assignments')->insert([
                    'school_id' => $school->id,
                    'title' => $title,
                    'description' => 'Complete the exercises and submit by due date.',
                    'subject' => $subject,
                    'class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'due_date' => $due,
                    'total_marks' => 100,
                    'status' => 'active',
                    'submissions_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
