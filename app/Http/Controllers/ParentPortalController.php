<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\ConsentForm;
use App\Models\ConsentResponse;
use App\Models\DisciplinaryRecord;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\ParentNotification;
use App\Models\Payment;
use App\Models\SchoolTrip;
use App\Models\SchoolTripEnrollment;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ParentAccessService;
use App\Services\SchoolTripService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ParentPortalController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccess,
        private InventoryService $inventoryService,
        private SchoolTripService $tripService,
    ) {}

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

        $activeConsentForms = ConsentForm::query()
            ->where('school_id', $parent->school_id)
            ->where('status', 'active')
            ->whereIn('target_audience', ['parents', 'all'])
            ->pluck('id');

        $respondedConsentIds = ConsentResponse::query()
            ->where('parent_user_id', $parent->id)
            ->whereIn('form_id', $activeConsentForms)
            ->pluck('form_id')
            ->unique();

        $pendingConsentForms = $activeConsentForms->diff($respondedConsentIds)->count();

        $recentAnnouncements = Announcement::query()
            ->where('school_id', $parent->school_id)
            ->where('is_active', true)
            ->whereIn('target_audience', ['all', 'parents'])
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->count();

        $recentResults = ExamResult::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('exam', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('is_published', true)
                        ->orWhereNotNull('results_approved_at');
                });
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $openDiscipline = DisciplinaryRecord::query()
            ->whereIn('student_id', $studentIds)
            ->where('incident_date', '>=', now()->subDays(90)->toDateString())
            ->count();

        return response()->json([
            'data' => [
                'children_count' => $studentIds->count(),
                'unread_notifications' => $unreadNotifications,
                'outstanding_balance' => (float) $outstandingBalance,
                'recent_absences' => $recentAbsences,
                'open_communications' => $openThreads,
                'pending_consent_forms' => $pendingConsentForms,
                'recent_announcements' => $recentAnnouncements,
                'recent_results' => $recentResults,
                'open_discipline' => $openDiscipline,
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
            ->whereHas('exam', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('is_published', true)
                        ->orWhereNotNull('results_approved_at');
                });
            })
            ->with(['exam:id,name,exam_date,total_marks,academic_year,term_id', 'exam.term:id,name', 'subject:id,name'])
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
                'term' => $result->exam?->term?->name,
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
        $studentId = $request->integer('student_id');

        if ($studentId) {
            $this->parentAccess->assertCanAccessStudent($parent, $studentId);
        }

        $query = ParentNotification::query()
            ->where('parent_user_id', $parent->id)
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId))
            ->with(['student:id,first_name,last_name,full_name,student_number'])
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $limit = min(max((int) $request->get('limit', 100), 1), 100);

        return response()->json(['data' => $query->limit($limit)->get()]);
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
            'staff_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('school_id', $parent->school_id)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('student_id')) {
            $this->parentAccess->assertCanAccessStudent($parent, (int) $request->student_id);
        }

        if ($request->filled('staff_user_id')) {
            $staff = User::query()
                ->where('school_id', $parent->school_id)
                ->whereKey($request->staff_user_id)
                ->first();
            if (! $staff || in_array($staff->role, [UserRole::Parent, UserRole::Student], true)) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['staff_user_id' => ['Selected staff member is invalid.']],
                ], 422);
            }
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

    /**
     * Active store catalogue (uniforms and other stock parents can order).
     */
    public function storeItems(Request $request)
    {
        $parent = $this->requireParent($request);

        $items = InventoryItem::query()
            ->where('school_id', $parent->school_id)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->when(
                $request->filled('type'),
                fn ($q) => $q->where('type', $request->string('type')),
                fn ($q) => $q->whereIn('type', ['uniform', 'stationery', 'book', 'equipment', 'other']),
            )
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'type', 'size', 'unit_price', 'currency', 'stock_quantity', 'description']);

        return response()->json(['data' => $items]);
    }

    /**
     * Parent orders stock for a linked child — charged to the student account.
     */
    public function buyStoreItems(Request $request)
    {
        $parent = $this->requireParent($request);

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:students,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $studentId = (int) $request->student_id;
        $this->parentAccess->assertCanAccessStudent($parent, $studentId);

        $sale = $this->inventoryService->createSale(
            schoolId: (int) $parent->school_id,
            items: $request->input('items'),
            paymentMethod: 'student_account',
            studentId: $studentId,
            soldBy: $parent->id,
            notes: $request->input('notes') ?? 'Parent portal uniform/store order',
        );

        return response()->json([
            'data' => $sale->load(['items.item', 'student:id,full_name,student_number']),
            'message' => 'Order placed. The amount has been added to the student fee account.',
        ], 201);
    }

    /**
     * Trips open for parent registration.
     */
    public function trips(Request $request)
    {
        $parent = $this->requireParent($request);
        $studentIds = $this->parentAccess->accessibleStudentIds($parent);

        $trips = SchoolTrip::query()
            ->where('school_id', $parent->school_id)
            ->where('is_active', true)
            ->where('open_for_registration', true)
            ->whereDate('trip_date', '>=', now()->toDateString())
            ->withCount(['enrollments as enrolled_count' => fn ($q) => $q->where('status', 'enrolled')])
            ->orderBy('trip_date')
            ->get()
            ->map(function (SchoolTrip $trip) use ($studentIds) {
                $myEnrollments = SchoolTripEnrollment::query()
                    ->where('school_trip_id', $trip->id)
                    ->whereIn('student_id', $studentIds)
                    ->where('status', 'enrolled')
                    ->pluck('student_id')
                    ->all();

                return [
                    'id' => $trip->id,
                    'name' => $trip->name,
                    'destination' => $trip->destination,
                    'trip_date' => $trip->trip_date?->toDateString(),
                    'return_date' => $trip->return_date?->toDateString(),
                    'fee_amount' => $trip->fee_amount,
                    'currency' => $trip->currency,
                    'capacity' => $trip->capacity,
                    'enrolled_count' => (int) $trip->enrolled_count,
                    'spots_remaining' => $trip->capacity === null
                        ? null
                        : max(0, (int) $trip->capacity - (int) $trip->enrolled_count),
                    'description' => $trip->description,
                    'enrolled_student_ids' => $myEnrollments,
                ];
            });

        return response()->json(['data' => $trips]);
    }

    /**
     * Register a linked child for a school trip (creates invoice when fee > 0).
     */
    public function enrollTrip(Request $request, int $id)
    {
        $parent = $this->requireParent($request);

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:students,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $studentId = (int) $request->student_id;
        $this->parentAccess->assertCanAccessStudent($parent, $studentId);

        $trip = SchoolTrip::where('school_id', $parent->school_id)->findOrFail($id);
        $enrollment = $this->tripService->enrollStudent($trip, $studentId, $parent->id);

        return response()->json([
            'data' => $enrollment->load(['student:id,full_name,student_number', 'invoice:id,invoice_number,amount,balance,status']),
            'message' => (float) $trip->fee_amount > 0
                ? 'Child registered. Trip fee has been added to their account.'
                : 'Child registered for the trip.',
        ], 201);
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
