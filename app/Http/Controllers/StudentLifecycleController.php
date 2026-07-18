<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\DisciplinaryRecord;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\HostelAllocation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentIntervention;
use App\Models\StudentMedicalProfile;
use App\Models\StudentStatusEvent;
use App\Models\StudentTransportAllocation;
use App\Models\Transaction;
use App\Services\StudentLifecycleActionService;
use App\Services\StudentPlacementService;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class StudentLifecycleController extends Controller
{
    public function __construct(
        private StudentPlacementService $placementService,
        private StudentLifecycleActionService $lifecycleActions,
        private StudentPromotionService $promotionService,
    ) {}

    public function show(Request $request, int $student)
    {
        $schoolId = $request->user()?->school_id;

        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with([
                'classModel.teacher:id,first_name,last_name,full_name',
                'stream:id,name,code',
                'house:id,name,code,color',
                'gradeLevel:id,name,order',
                'school:id,name,code',
                'guardians',
                'parents:id,name,email,phone',
            ])
            ->findOrFail($student);

        $enrollments = Enrollment::query()
            ->where('student_id', $student->id)
            ->with(['classModel:id,name', 'stream:id,name', 'house:id,name'])
            ->orderByDesc('enrolled_at')
            ->get();

        $applications = EnrollmentApplication::query()
            ->where('school_id', $student->school_id)
            ->where(function ($q) use ($student) {
                $q->where('student_id', $student->id)
                    ->orWhere('email', $student->email)
                    ->orWhere('phone', $student->phone);
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $attendanceSummary = Attendance::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentAttendance = Attendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(20)
            ->get(['id', 'date', 'status', 'class_id', 'subject_id', 'remarks']);

        $examHistory = ExamResult::query()
            ->where('student_id', $student->id)
            ->with(['exam:id,name,exam_date,academic_year', 'subject:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $finance = [
            'balance' => (float) $student->balance,
            'currency' => $student->currency,
            'invoices' => Invoice::where('student_id', $student->id)->orderByDesc('created_at')->limit(20)->get(),
            'payments' => Payment::where('student_id', $student->id)->orderByDesc('date')->limit(20)->get(),
            'transactions' => Transaction::where('student_id', $student->id)->orderByDesc('created_at')->limit(30)->get(),
        ];

        $discipline = DisciplinaryRecord::query()
            ->where('student_id', $student->id)
            ->orderByDesc('incident_date')
            ->get();

        $counselling = StudentIntervention::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $transport = StudentTransportAllocation::query()
            ->where('student_id', $student->id)
            ->with('route.vehicle', 'route.driver')
            ->get();

        $hostel = HostelAllocation::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->with(['bed.room.hostel'])
            ->first();

        $medical = StudentMedicalProfile::where('student_id', $student->id)->first();

        $statusEvents = StudentStatusEvent::query()
            ->where('student_id', $student->id)
            ->with('performer:id,name')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $homeroom = $student->classModel?->teacher;

        return response()->json([
            'data' => [
                'student' => $student,
                'placement' => [
                    'class' => $student->classModel,
                    'stream' => $student->stream,
                    'house' => $student->house,
                    'grade_level' => $student->gradeLevel,
                    'homeroom_teacher' => $homeroom,
                    'subject_package' => $this->placementService->subjectPackageFor($student),
                ],
                'admission' => [
                    'applications' => $applications,
                    'enrollments' => $enrollments,
                    'status' => $student->status,
                    'status_reason' => $student->status_reason,
                    'repetition_count' => $student->repetition_count,
                    'previous_school' => $student->previous_school,
                    'exited_at' => $student->exited_at,
                ],
                'status_events' => $statusEvents,
                'academics' => [
                    'grades' => $student->grades()->orderByDesc('year')->limit(50)->get(),
                    'exam_results' => $examHistory,
                ],
                'attendance' => [
                    'summary' => $attendanceSummary,
                    'recent' => $recentAttendance,
                ],
                'finance' => $finance,
                'discipline' => $discipline,
                'counselling' => $counselling,
                'medical' => $medical,
                'transport' => $transport,
                'hostel' => $hostel,
                'guardians' => $student->guardians,
                'parents' => $student->parents,
            ],
        ]);
    }

    public function place(Request $request, int $student)
    {
        $schoolId = $request->user()?->school_id;
        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($student);

        $data = Validator::make($request->all(), [
            'class_id' => 'required|integer|exists:classes,id',
            'stream_id' => 'nullable|integer|exists:streams,id',
            'house_id' => 'nullable|integer|exists:houses,id',
            'academic_year' => 'required|string|max:20',
            'reason' => 'nullable|string|max:255',
            'effective_date' => 'nullable|date',
            'apply_fees' => 'nullable|boolean',
        ])->validate();

        $placed = $this->placementService->place($student, [
            ...$data,
            'reason' => $data['reason'] ?? 'mid_year_transfer',
            'apply_fees' => (bool) ($data['apply_fees'] ?? false),
        ], $request->user()?->id);

        return response()->json([
            'message' => 'Student placement updated.',
            'data' => $placed,
        ]);
    }

    public function transition(Request $request, int $student)
    {
        $schoolId = $request->user()?->school_id;
        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($student);

        $data = Validator::make($request->all(), [
            'action' => 'required|string|in:suspend,reinstate,withdraw,expel,transfer_out,graduate,deactivate,activate',
            'reason' => 'nullable|string|max:1000',
            'effective_date' => 'nullable|date',
            'to_school_id' => 'nullable|integer|exists:schools,id',
            'issue_tc' => 'nullable|boolean',
            'deactivate_account' => 'nullable|boolean',
        ])->validate();

        $result = $this->lifecycleActions->transition($student, $data, $request->user());

        return response()->json([
            'message' => 'Student lifecycle action completed.',
            'data' => $result,
        ]);
    }

    public function promoteOne(Request $request, int $student)
    {
        $schoolId = $request->user()?->school_id;
        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($student);

        $data = Validator::make($request->all(), [
            'academic_year' => 'required|string|max:20',
            'next_academic_year' => 'required|string|max:20',
            'pass_mark' => 'nullable|numeric|min:0|max:100',
        ])->validate();

        $result = $this->promotionService->runYearEnd(
            schoolId: (int) $student->school_id,
            academicYear: $data['academic_year'],
            nextAcademicYear: $data['next_academic_year'],
            passMark: (float) ($data['pass_mark'] ?? 50),
            studentIds: [$student->id],
            processedBy: $request->user()?->id,
        );

        return response()->json([
            'message' => 'Promotion decision applied.',
            'data' => [
                ...$result,
                'student' => $student->fresh(['classModel', 'gradeLevel', 'stream', 'house']),
            ],
        ]);
    }

    public function transferCertificate(Request $request, int $student)
    {
        $schoolId = $request->user()?->school_id;
        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($student);

        $cert = $this->lifecycleActions->downloadTransferCertificate($student);
        if (! $cert) {
            $result = $this->lifecycleActions->transition($student, [
                'action' => 'transfer_out',
                'reason' => $request->input('reason', 'Transfer certificate issued'),
                'issue_tc' => true,
                'deactivate_account' => false,
            ], $request->user());
            $cert = $result['certificate'];
        }

        return response($cert->content_html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$student->student_number.'_transfer_certificate.html"',
        ]);
    }

    public function transcript(Request $request, int $student)
    {
        $schoolId = $request->user()?->school_id;
        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['school:id,name', 'gradeLevel:id,name', 'classModel:id,name'])
            ->findOrFail($student);

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->orderBy('year')
            ->orderBy('term')
            ->orderBy('subject')
            ->get();

        $examResults = ExamResult::query()
            ->where('student_id', $student->id)
            ->with(['exam:id,name,exam_date,academic_year', 'subject:id,name'])
            ->orderBy('created_at')
            ->get();

        $schoolName = e((string) ($student->school?->name ?? 'School'));
        $name = e((string) $student->full_name);
        $number = e((string) $student->student_number);
        $class = e((string) ($student->class ?? '—'));
        $generated = e(now()->timezone('Africa/Harare')->format('d M Y, H:i'));

        $gradeRows = $grades->map(function (Grade $g) {
            return '<tr><td>'.e((string) $g->subject).'</td><td>'.e((string) $g->score).'</td><td>'.e((string) $g->total)
                .'</td><td>'.e((string) $g->grade).'</td><td>'.e((string) $g->term).'</td><td>'.e((string) $g->year).'</td></tr>';
        })->implode('');
        if ($gradeRows === '') {
            $gradeRows = '<tr><td colspan="6">No grades recorded.</td></tr>';
        }

        $examRows = $examResults->map(function (ExamResult $r) {
            $date = $r->exam?->exam_date
                ? Carbon::parse($r->exam->exam_date)->format('d M Y')
                : '—';

            return '<tr><td>'.e((string) ($r->exam?->name ?? '—')).'</td><td>'.e((string) ($r->subject?->name ?? '—'))
                .'</td><td>'.e($date).'</td><td>'.e((string) $r->marks_obtained).'</td><td>'.e((string) $r->total_marks)
                .'</td><td>'.e((string) $r->percentage).'%</td><td>'.e((string) $r->grade).'</td></tr>';
        })->implode('');
        if ($examRows === '') {
            $examRows = '<tr><td colspan="7">No exam results recorded.</td></tr>';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>{$name} — Official transcript</title>
<style>
body{font-family:Georgia,serif;margin:32px;color:#111}
h1,h2{font-family:Arial,Helvetica,sans-serif;margin:0 0 8px}
table{width:100%;border-collapse:collapse;margin:12px 0 28px;font-size:14px}
th,td{border:1px solid #bbb;padding:8px 10px;text-align:left}
th{background:#f3f4f6}
.meta{color:#444;margin-bottom:24px;font-size:14px}
</style></head><body>
<h1>{$schoolName}</h1>
<h2>Official academic transcript</h2>
<div class="meta">
<div><strong>Student:</strong> {$name}</div>
<div><strong>Student number:</strong> {$number}</div>
<div><strong>Class:</strong> {$class}</div>
<div><strong>Generated:</strong> {$generated}</div>
</div>
<h2>Continuous assessment</h2>
<table><thead><tr><th>Subject</th><th>Score</th><th>Total</th><th>Grade</th><th>Term</th><th>Year</th></tr></thead>
<tbody>{$gradeRows}</tbody></table>
<h2>Examination results</h2>
<table><thead><tr><th>Exam</th><th>Subject</th><th>Date</th><th>Marks</th><th>Total</th><th>%</th><th>Grade</th></tr></thead>
<tbody>{$examRows}</tbody></table>
</body></html>
HTML;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$student->student_number.'_transcript.html"',
        ]);
    }
}
