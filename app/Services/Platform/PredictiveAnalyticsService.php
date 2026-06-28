<?php

namespace App\Services\Platform;

use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Support\Collection;

class PredictiveAnalyticsService
{
    public function studentRiskScores(int $schoolId, ?int $limit = 50): Collection
    {
        $students = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->limit($limit ?? 50)
            ->get();

        return $students->map(function (Student $student) {
            $attendanceRate = $this->attendanceRate($student->id);
            $avgScore = $this->averageExamScore($student->id);
            $risk = $this->calculateDropoutRisk($attendanceRate, $avgScore);

            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'attendance_rate' => round($attendanceRate, 1),
                'average_score_percent' => round($avgScore, 1),
                'dropout_risk_score' => $risk,
                'risk_level' => match (true) {
                    $risk >= 70 => 'high',
                    $risk >= 40 => 'medium',
                    default => 'low',
                },
                'performance_prediction' => $avgScore >= 60 ? 'likely_pass' : ($avgScore >= 40 ? 'at_risk' : 'likely_fail'),
            ];
        })->sortByDesc('dropout_risk_score')->values();
    }

    protected function attendanceRate(int $studentId): float
    {
        $records = Attendance::where('student_id', $studentId)->get();
        if ($records->isEmpty()) {
            return 100.0;
        }

        $present = $records->where('status', 'present')->count();

        return ($present / $records->count()) * 100;
    }

    protected function averageExamScore(int $studentId): float
    {
        $results = ExamResult::where('student_id', $studentId)->get();
        if ($results->isEmpty()) {
            return 50.0;
        }

        return (float) $results->avg(fn ($r) => $r->total_marks > 0 ? ($r->marks_obtained / $r->total_marks) * 100 : 0);
    }

    protected function calculateDropoutRisk(float $attendanceRate, float $avgScore): int
    {
        $attendanceRisk = max(0, 100 - $attendanceRate);
        $scoreRisk = max(0, 100 - $avgScore);

        return (int) min(100, round(($attendanceRisk * 0.55) + ($scoreRisk * 0.45)));
    }
}
