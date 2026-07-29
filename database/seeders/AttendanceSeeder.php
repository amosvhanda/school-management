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
        if (! $admin) {
            return;
        }

        $statuses = ['present', 'present', 'present', 'present', 'absent', 'late', 'excused'];

        foreach (range(0, 6) as $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();

            foreach (Student::where('status', 'active')->get() as $student) {
                if (! $student->school_id) {
                    continue;
                }

                $classModel = $this->resolveClass($student);
                if (! $classModel) {
                    continue;
                }

                $teacher = $classModel->teacher;
                $status = $statuses[array_rand($statuses)];

                $payload = [
                    'teacher_id' => $teacher?->id,
                    'status' => $status,
                    'time_in' => in_array($status, ['present', 'late'], true) ? '07:30:00' : null,
                    'marked_by' => $admin->id,
                    'school_id' => $student->school_id,
                ];

                // Match by calendar day — Eloquent's date cast stores midnight
                // datetimes, so equality on "Y-m-d" misses existing rows and
                // re-seeding then hits the (student_id, date, class_id) unique key.
                $existing = Attendance::query()
                    ->where('student_id', $student->id)
                    ->where('class_id', $classModel->id)
                    ->whereDate('date', $date)
                    ->first();

                if ($existing) {
                    $existing->update($payload);

                    continue;
                }

                Attendance::create([
                    'student_id' => $student->id,
                    'date' => $date,
                    'class_id' => $classModel->id,
                    ...$payload,
                ]);
            }
        }
    }

    private function resolveClass(Student $student): ?ClassModel
    {
        if ($student->class_id) {
            $byId = ClassModel::with('teacher')
                ->where('school_id', $student->school_id)
                ->where('id', $student->class_id)
                ->first();
            if ($byId) {
                return $byId;
            }
        }

        if (! $student->class) {
            return null;
        }

        return ClassModel::with('teacher')
            ->where('school_id', $student->school_id)
            ->where('name', $student->class)
            ->first();
    }
}
