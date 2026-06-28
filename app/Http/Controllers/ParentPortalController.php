<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\DisciplinaryRecord;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\ParentNotification;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ParentAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ParentPortalController extends Controller
{
    public function __construct(private ParentAccessService $parentAccess) {}

    public function dashboard(Request $request)
    {
        $parent = $this->requireParent($request);
        $studentIds = $this->parentAccess->accessibleStudentIds($parent);

        $unreadNotifications = ParentNotification::query()
            ->where('parent_user_id', $parent->id)
            ->whereNull('read_at')
            ->count();

        $outstandingBalance = Student::query()
            ->whereIn('id', $studentIds)
            ->sum('balance');

        $recentAbsences = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'absent')
            ->whereDate('date', '>=', now()->subDays(30))
            ->count();

        $openThreads = CommunicationThread::query()
            ->where('parent_user_id', $parent->id)
            ->where('status', 'open')
            ->count();

        return response()->json([
            'data' => [
                'children_count' => $studentIds->count(),
                'unread_notifications' => $unreadNotifications,
                'outstanding_balance' => (float) $outstandingBalance,
                'recent_absences' => $recentAbsences,
                'open_communications' => $openThreads,
            ],
        ]);
    }

    public function children(Request $request)
    {
        $parent = $this->requireParent($request);
        $studentIds = $this->parentAccess->accessibleStudentIds($parent);

        $children = Student::query()
            ->whereIn('id', $studentIds)
            ->where('status', 'active')
            ->with(['classModel:id,name', 'gradeLevel:id,name'])
            ->get()
            ->map(fn (Student $student) => $this->formatStudentSummary($student));

        return response()->json(['data' => $children]);
    }

    public function results(Request $request, int $studentId)
    {
        $parent = $this->requireParent($request);
        $student = $this->parentAccess->assertCanAccessStudent($parent, $studentId);

        $examResults = ExamResult::query()
            ->where('student_id', $student->id)
            ->with(['exam:id,name,exam_date,total_marks,academic_year', 'subject:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ExamResult $result) => [
                'id' => $result->id,
                'exam_id' => $result->exam_id,
                'exam_name' => $result->exam?->name,
                'exam_date' => $result->exam?->exam_date?->toDateString(),
                'subject' => $result->subject?->name,
                'marks_obtained' => (float) $result->marks_obtained,
                'total_marks' => (float) $result->total_marks,
                'percentage' => (float) $result->percentage,
                'grade' => $result->grade,
                'remarks' => $result->remarks,
                'academic_year' => $result->exam?->academic_year,
            ]);

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->orderByDesc('year')
            ->orderBy('term')
            ->get()
            ->map(fn (Grade $grade) => [
                'id' => $grade->id,
                'subject' => $grade->subject,
                'score' => (float) $grade->score,
                'total' => (float) $grade->total,
                'grade' => $grade->grade,
                'term' => $grade->term,
                'year' => $grade->year,
            ]);

        return response()->json([
            'data' => [
                'student' => $this->formatStudentSummary($student),
                'exam_results' => $examResults,
                'report_card_grades' => $grades,
            ],
        ]);
    }

    public function attendance(Request $request, int $studentId)
    {
        $parent = $this->requireParent($request);
        $student = $this->parentAccess->assertCanAccessStudent($parent, $studentId);

        $query = Attendance::query()
            ->where('student_id', $student->id)
            ->with(['classModel:id,name', 'subject:id,name']);

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        $records = $query->orderByDesc('date')->limit(100)->get()->map(fn (Attendance $row) => [
            'id' => $row->id,
            'date' => $row->date?->toDateString(),
            'status' => $row->status,
            'class' => $row->classModel?->name,
            'subject' => $row->subject?->name ?? $row->subject,
            'remarks' => $row->remarks,
        ]);

        $summary = Attendance::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'data' => [
                'student' => $this->formatStudentSummary($student),
                'summary' => $summary,
                'records' => $records,
            ],
        ]);
    }

    public function fees(Request $request, int $studentId)
    {
        $parent = $this->requireParent($request);
        $student = $this->parentAccess->assertCanAccessStudent($parent, $studentId);

        $invoices = Invoice::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'description' => $invoice->description,
                'amount' => (float) $invoice->amount,
                'amount_paid' => (float) $invoice->amount_paid,
                'balance' => (float) $invoice->balance,
                'currency' => $invoice->currency,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toDateString(),
            ]);

        $payments = Payment::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(50)
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'status' => $payment->status,
                'date' => $payment->date?->toDateString(),
            ]);

        $transactions = Transaction::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Transaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'description' => $tx->description,
                'debit' => (float) $tx->debit,
                'credit' => (float) $tx->credit,
                'balance' => (float) $tx->balance,
                'currency' => $tx->currency,
                'created_at' => $tx->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'data' => [
                'student' => $this->formatStudentSummary($student),
                'outstanding_balance' => (float) $student->balance,
                'currency' => $student->currency,
                'invoices' => $invoices,
                'payments' => $payments,
                'transactions' => $transactions,
            ],
        ]);
    }

    public function discipline(Request $request, int $studentId)
    {
        $parent = $this->requireParent($request);
        $student = $this->parentAccess->assertCanAccessStudent($parent, $studentId);

        $records = DisciplinaryRecord::query()
            ->where('student_id', $student->id)
            ->orderByDesc('incident_date')
            ->get()
            ->map(fn (DisciplinaryRecord $record) => [
                'id' => $record->id,
                'incident_date' => $record->incident_date?->toDateString(),
                'category' => $record->category,
                'severity' => $record->severity,
                'description' => $record->description,
                'action_taken' => $record->action_taken,
                'parent_notified' => $record->parent_notified,
                'parent_notified_at' => $record->parent_notified_at?->toDateTimeString(),
            ]);

        return response()->json([
            'data' => [
                'student' => $this->formatStudentSummary($student),
                'records' => $records,
            ],
        ]);
    }

    public function progress(Request $request, int $studentId)
    {
        $parent = $this->requireParent($request);
        $student = $this->parentAccess->assertCanAccessStudent($parent, $studentId);

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->orderByDesc('year')
            ->orderBy('term')
            ->get();

        $bySubject = $grades->groupBy('subject')->map(function ($rows, $subject) {
            $avg = $rows->avg(fn (Grade $g) => $g->total > 0 ? ($g->score / $g->total) * 100 : 0);

            return [
                'subject' => $subject,
                'average_percent' => round($avg, 1),
                'records' => $rows->count(),
            ];
        })->values();

        return response()->json([
            'data' => [
                'student' => $this->formatStudentSummary($student),
                'by_subject' => $bySubject,
                'recent_grades' => $grades->take(20)->map(fn (Grade $g) => [
                    'subject' => $g->subject,
                    'score' => (float) $g->score,
                    'total' => (float) $g->total,
                    'grade' => $g->grade,
                    'term' => $g->term,
                    'year' => $g->year,
                ]),
            ],
        ]);
    }

    public function announcements(Request $request)
    {
        $parent = $this->requireParent($request);

        $announcements = Announcement::query()
            ->where('school_id', $parent->school_id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('target_audience', 'all')
                    ->orWhere('target_audience', 'parents');
            })
            ->orderByDesc('date')
            ->limit(50)
            ->get();

        return response()->json(['data' => $announcements]);
    }

    public function notifications(Request $request)
    {
        $parent = $this->requireParent($request);

        $query = ParentNotification::query()
            ->where('parent_user_id', $parent->id)
            ->with(['student:id,first_name,last_name,full_name,student_number'])
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        return response()->json(['data' => $query->limit(100)->get()]);
    }

    public function markNotificationRead(Request $request, int $id)
    {
        $parent = $this->requireParent($request);

        $notification = ParentNotification::query()
            ->where('parent_user_id', $parent->id)
            ->findOrFail($id);

        $notification->update(['read_at' => now()]);

        return response()->json(['data' => $notification->fresh()]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $parent = $this->requireParent($request);

        ParentNotification::query()
            ->where('parent_user_id', $parent->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function threads(Request $request)
    {
        $parent = $this->requireParent($request);

        $threads = CommunicationThread::query()
            ->where('parent_user_id', $parent->id)
            ->with(['student:id,full_name,student_number', 'staff:id,name,first_name,last_name'])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json(['data' => $threads]);
    }

    public function createThread(Request $request)
    {
        $parent = $this->requireParent($request);

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'student_id' => 'nullable|exists:students,id',
            'staff_user_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('student_id')) {
            $this->parentAccess->assertCanAccessStudent($parent, (int) $request->student_id);
        }

        $thread = CommunicationThread::create([
            'school_id' => $parent->school_id,
            'student_id' => $request->student_id,
            'parent_user_id' => $parent->id,
            'staff_user_id' => $request->staff_user_id,
            'subject' => $request->subject,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        CommunicationMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $parent->id,
            'body' => $request->message,
        ]);

        return response()->json(['data' => $thread->load(['student', 'staff'])], 201);
    }

    public function threadMessages(Request $request, int $threadId)
    {
        $parent = $this->requireParent($request);

        $thread = CommunicationThread::query()
            ->where('parent_user_id', $parent->id)
            ->findOrFail($threadId);

        $messages = $thread->messages()
            ->with('sender:id,name,first_name,last_name,role')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => [
                'thread' => $thread->load(['student', 'staff']),
                'messages' => $messages,
            ],
        ]);
    }

    public function sendMessage(Request $request, int $threadId)
    {
        $parent = $this->requireParent($request);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $thread = CommunicationThread::query()
            ->where('parent_user_id', $parent->id)
            ->findOrFail($threadId);

        $message = CommunicationMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $parent->id,
            'body' => $request->body,
        ]);

        $thread->update(['last_message_at' => now(), 'status' => 'open']);

        return response()->json(['data' => $message->load('sender')], 201);
    }

    protected function requireParent(Request $request): User
    {
        $user = $request->user();

        if (! $this->parentAccess->isParent($user)) {
            throw new AccessDeniedHttpException('Parent portal access is restricted to parent accounts.');
        }

        return $user;
    }

    protected function formatStudentSummary(Student $student): array
    {
        return [
            'id' => $student->id,
            'firstName' => $student->first_name,
            'surname' => $student->last_name,
            'fullName' => $student->full_name,
            'studentNumber' => $student->student_number,
            'class' => $student->classModel?->name ?? $student->class,
            'class_id' => $student->class_id,
            'grade_level' => $student->gradeLevel?->name,
            'balance' => (float) $student->balance,
            'currency' => $student->currency,
        ];
    }
}
