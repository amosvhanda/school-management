<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        $statuses = ['present', 'present', 'present', 'present', 'absent', 'late', 'excused'];

        foreach (range(0, 6) as $daysAgo) {
            $date = now()->subDays($daysAgo)->format('Y-m-d');
            foreach (Student::where('status', 'active')->get() as $student) {
                if (!$student->school_id) {
                    continue;
                }
                $classModel = ClassModel::with('teacher')
                    ->where('school_id', $student->school_id)
                    ->where('name', $student->class)
                    ->first();
                $teacher = $classModel?->teacher;
                $status = $statuses[array_rand($statuses)];
                $att = Attendance::where('student_id', $student->id)->whereDate('date', $date)->first();
                $updatePayload = [
                    'class_id' => $classModel?->id,
                    'teacher_id' => $teacher?->id,
                    'status' => $status,
                    'time_in' => in_array($status, ['present', 'late'], true) ? '07:30:00' : null,
                    'marked_by' => $admin->id,
                    'school_id' => $student->school_id,
                ];
                if ($att) {
                    $att->update($updatePayload);
                } else {
                    Attendance::create(array_merge(
                        ['student_id' => $student->id, 'date' => $date],
                        $updatePayload
                    ));
                }
            }
        }
    }
}
