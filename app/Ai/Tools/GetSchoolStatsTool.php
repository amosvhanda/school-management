<?php

namespace App\Ai\Tools;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetSchoolStatsTool implements Tool
{
    public function __construct(private int $schoolId) {}

    public function description(): Stringable|string
    {
        return 'Get summary statistics for the current school: enrollment, staff, attendance today, and fee totals.';
    }

    public function handle(Request $request): Stringable|string
    {
        $today = now()->toDateString();

        $studentQuery = Student::query()->where('school_id', $this->schoolId);
        $teacherQuery = Teacher::query()->where('school_id', $this->schoolId);
        $attendanceToday = Attendance::query()
            ->where('school_id', $this->schoolId)
            ->whereDate('date', $today)
            ->get();

        $stats = [
            'date' => $today,
            'students' => [
                'total' => (clone $studentQuery)->count(),
                'active' => (clone $studentQuery)->where('status', 'active')->count(),
            ],
            'teachers' => [
                'total' => (clone $teacherQuery)->count(),
                'active' => (clone $teacherQuery)->where('status', 'active')->count(),
            ],
            'classes' => ClassModel::query()->where('school_id', $this->schoolId)->count(),
            'attendance_today' => [
                'present' => $attendanceToday->where('status', 'present')->count(),
                'absent' => $attendanceToday->where('status', 'absent')->count(),
                'late' => $attendanceToday->where('status', 'late')->count(),
                'excused' => $attendanceToday->where('status', 'excused')->count(),
                'total' => $attendanceToday->count(),
            ],
            'finance' => [
                'outstanding_fees' => Invoice::query()
                    ->where('school_id', $this->schoolId)
                    ->where('status', '!=', 'paid')
                    ->sum('balance'),
                'payments_today' => Payment::query()
                    ->where('school_id', $this->schoolId)
                    ->whereDate('date', $today)
                    ->where('status', 'completed')
                    ->sum('amount'),
            ],
        ];

        return json_encode($stats, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
