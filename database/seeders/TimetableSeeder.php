<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\Teacher;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class TimetableSeeder extends Seeder
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    private const SLOTS = [
        ['07:30', '08:15'],
        ['08:15', '09:00'],
        ['09:15', '10:00'],
        ['10:00', '10:45'],
        ['11:00', '11:45'],
        ['11:45', '12:30'],
    ];

    public function run(): void
    {
        $subjects = ZimbabweData::SUBJECTS;
        foreach (ClassModel::with('teacher')->get() as $class) {
            if (!$class->school_id) {
                continue;
            }
            $teacher = $class->teacher ?? Teacher::where('school_id', $class->school_id)->first();
            if (!$teacher) {
                continue;
            }
            $idx = 0;
            foreach (self::DAYS as $day) {
                foreach (self::SLOTS as $slot) {
                    $subjectName = $subjects[$idx % count($subjects)];
                    $subjectModel = Subject::where('school_id', $class->school_id)->where('name', $subjectName)->first();
                    $start = strlen($slot[0]) === 5 ? $slot[0] . ':00' : $slot[0];
                    $end = strlen($slot[1]) === 5 ? $slot[1] . ':00' : $slot[1];
                    $tt = Timetable::where('class_id', $class->id)->where('day', $day)
                        ->where(function ($q) use ($start) {
                            $q->where('start_time', $start)->orWhereRaw('TIME(start_time) = ?', [$start]);
                        })
                        ->first();
                    $payload = [
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subjectModel?->id,
                        'subject' => $subjectName,
                        'end_time' => $end,
                        'room' => 'Room ' . (100 + ($class->id % 20)),
                        'school_id' => $class->school_id,
                    ];
                    if ($tt) {
                        $tt->update($payload);
                    } else {
                        Timetable::create(array_merge(
                            ['class_id' => $class->id, 'day' => $day, 'start_time' => $start],
                            $payload
                        ));
                    }
                    $idx++;
                }
            }
        }
    }
}
