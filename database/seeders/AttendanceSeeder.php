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
            $date = now()->subDays($daysAgo)->format('Y-m-d');
            foreach (Student::where('status', 'active')->get() as $student) {
                if (! $student->school_id) {
                    continue;
                }

                $classModel = $this->resolveClass($student);
                $classId = $classModel?->id;
                $teacher = $classModel?->teacher;
                $status = $statuses[array_rand($statuses)];

                $payload = [
                    'class_id' => $classId,
                    'teacher_id' => $teacher?->id,
                    'status' => $status,
                    'time_in' => in_array($status, ['present', 'late'], true) ? '07:30:00' : null,
                    'marked_by' => $admin->id,
                    'school_id' => $student->school_id,
                ];

                $att = $this->findForUpsert((int) $student->id, $date, $classId);

                if ($att) {
                    $att->update($payload);
                } else {
                    Attendance::create(array_merge(
                        ['student_id' => $student->id, 'date' => $date],
                        $payload
                    ));
                }
            }
        }
    }

    private function resolveClass(Student $student): ?ClassModel
    {
        if ($student->class_id) {
            $byId = ClassModel::with('teacher')->find($student->class_id);
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

    /**
     * Match the unique key (student_id, date, class_id), preferring an exact
     * class row and falling back to a legacy null-class row for the same day.
     */
    private function findForUpsert(int $studentId, string $date, ?int $classId): ?Attendance
    {
        $query = Attendance::query()
            ->where('student_id', $studentId)
            ->whereDate('date', $date);

        if ($classId !== null) {
            $query->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)->orWhereNull('class_id');
            })->orderByRaw('CASE WHEN class_id IS NULL THEN 1 ELSE 0 END');
        } else {
            $query->whereNull('class_id');
        }

        return $query->first();
    }
}
