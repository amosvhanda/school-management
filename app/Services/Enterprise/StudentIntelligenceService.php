<?php

namespace App\Services\Enterprise;

use App\Models\AdmissionScore;
use App\Models\AlumniRecord;
use App\Models\BehaviorPoint;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\StudentTimelineEvent;
use App\Services\Platform\PredictiveAnalyticsService;
use Illuminate\Support\Collection;

class StudentIntelligenceService
{
    public function __construct(private PredictiveAnalyticsService $predictive) {}

    public function scoreAdmission(int $schoolId, array $data): AdmissionScore
    {
        $total = (float) ($data['academic_score'] ?? 0) + (float) ($data['interview_score'] ?? 0);
        $recommendation = match (true) {
            $total >= 80 => 'strong_accept',
            $total >= 60 => 'accept',
            $total >= 40 => 'waitlist',
            default => 'reject',
        };

        return AdmissionScore::create([
            'school_id' => $schoolId,
            'enrollment_application_id' => $data['enrollment_application_id'] ?? null,
            'applicant_name' => $data['applicant_name'],
            'academic_score' => $data['academic_score'] ?? 0,
            'interview_score' => $data['interview_score'] ?? 0,
            'total_score' => $total,
            'recommendation' => $recommendation,
        ]);
    }

    public function profile(int $studentId): array
    {
        $student = Student::with(['gradeLevel', 'class'])->findOrFail($studentId);
        $examAvg = ExamResult::where('student_id', $studentId)
            ->get()
            ->avg(fn ($r) => $r->total_marks > 0 ? ($r->marks_obtained / $r->total_marks) * 100 : 0) ?? 0;
        $behaviorTotal = BehaviorPoint::where('student_id', $studentId)->sum('points');
        $balance = (float) ($student->balance ?? 0);

        $risk = $this->predictive->studentRiskScores($student->school_id, 1)
            ->firstWhere('student_id', $studentId);

        return [
            'student' => $student->only(['id', 'full_name', 'student_number', 'status']),
            'academic' => ['average_percent' => round($examAvg, 2)],
            'behavior' => ['total_points' => (int) $behaviorTotal],
            'financial' => ['balance' => $balance, 'arrears' => $balance > 0],
            'risk' => $risk,
        ];
    }

    public function recordTimelineEvent(int $schoolId, array $data): StudentTimelineEvent
    {
        return StudentTimelineEvent::create(array_merge($data, [
            'school_id' => $schoolId,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]));
    }

    public function timeline(int $studentId, ?int $schoolId = null): Collection
    {
        return StudentTimelineEvent::query()
            ->where('student_id', $studentId)
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get();
    }

    public function registerAlumni(int $schoolId, Student $student, array $data = []): AlumniRecord
    {
        return AlumniRecord::query()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'student_id' => $student->id,
            ],
            [
                'full_name' => $student->full_name,
                'graduation_year' => (string) ($data['graduation_year'] ?? now()->year),
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'current_occupation' => $data['current_occupation'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAlumni(int $schoolId, array $data): AlumniRecord
    {
        return AlumniRecord::create([
            'school_id' => $schoolId,
            'student_id' => $data['student_id'] ?? null,
            'full_name' => $data['full_name'],
            'graduation_year' => (string) $data['graduation_year'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'current_occupation' => $data['current_occupation'] ?? null,
            'engagement_history' => $data['engagement_history'] ?? [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAlumni(AlumniRecord $alumni, array $data): AlumniRecord
    {
        $alumni->fill(collect($data)->only([
            'full_name',
            'graduation_year',
            'email',
            'phone',
            'current_occupation',
        ])->all());

        if (isset($data['graduation_year'])) {
            $alumni->graduation_year = (string) $data['graduation_year'];
        }

        $alumni->save();

        return $alumni->fresh();
    }

    public function recordEngagement(AlumniRecord $alumni, string $type, ?string $note = null): AlumniRecord
    {
        $history = $alumni->engagement_history ?? [];
        $history[] = [
            'type' => $type,
            'note' => $note,
            'at' => now()->toIso8601String(),
        ];

        $alumni->update(['engagement_history' => $history]);

        return $alumni->fresh();
    }

    public function earlyWarnings(int $schoolId): Collection
    {
        $riskStudents = $this->predictive->studentRiskScores($schoolId, 100)
            ->where('risk_level', 'high');

        $arrears = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('balance', '>', 0)
            ->get(['id', 'full_name', 'balance'])
            ->map(fn ($s) => [
                'type' => 'unpaid_fees',
                'student_id' => $s->id,
                'student_name' => $s->full_name,
                'balance' => (float) $s->balance,
            ]);

        return $riskStudents->map(fn ($r) => array_merge($r, ['type' => 'dropout_risk']))
            ->concat($arrears)
            ->values();
    }
}
