<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Academic performance report: aggregates from grades (by class, subject, student).
     */
    public function academicPerformance(Request $request)
    {
        $query = Grade::with(['student', 'classModel', 'teacher']);

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }
        if ($request->filled('year')) {
            $query->where('year', (int) $request->year);
        }
        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        $grades = $query->orderBy('subject')->orderBy('student_id')->get();

        $bySubject = $grades->groupBy('subject')->map(function ($rows) {
            $avg = $rows->avg(fn ($g) => $g->total > 0 ? ($g->score / $g->total) * 100 : 0);
            return [
                'subject' => $rows->first()->subject,
                'count' => $rows->count(),
                'average_percent' => round($avg, 1),
            ];
        })->values();

        $byClass = $grades->groupBy('class_id')->map(function ($rows) {
            $avg = $rows->avg(fn ($g) => $g->total > 0 ? ($g->score / $g->total) * 100 : 0);
            $class = $rows->first()->classModel;
            return [
                'class_id' => $rows->first()->class_id,
                'class_name' => $class?->name,
                'count' => $rows->count(),
                'average_percent' => round($avg, 1),
            ];
        })->values();

        return response()->json([
            'data' => [
                'report_type' => 'academic-performance',
                'generated_at' => now()->toIso8601String(),
                'total_records' => $grades->count(),
                'by_subject' => $bySubject,
                'by_class' => $byClass,
                'records' => $grades->take(100)->map(fn ($g) => [
                    'student_id' => $g->student_id,
                    'student_name' => $g->student?->full_name,
                    'subject' => $g->subject,
                    'score' => $g->score,
                    'total' => $g->total,
                    'grade' => $g->grade,
                    'term' => $g->term,
                    'year' => $g->year,
                ]),
            ],
        ]);
    }

    /**
     * Attendance report: aggregates from attendance (by class, date range, status).
     */
    public function attendance(Request $request)
    {
        $query = Attendance::with(['student', 'classModel']);

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        $records = $query->orderBy('date')->orderBy('class_id')->get();

        $byStatus = $records->groupBy('status')->map(fn ($rows) => $rows->count())->all();
        $byClass = $records->groupBy('class_id')->map(function ($rows) {
            $class = $rows->first()->classModel;
            return [
                'class_id' => $rows->first()->class_id,
                'class_name' => $class?->name,
                'total' => $rows->count(),
                'present' => $rows->where('status', 'present')->count(),
                'absent' => $rows->where('status', 'absent')->count(),
                'late' => $rows->where('status', 'late')->count(),
                'excused' => $rows->where('status', 'excused')->count(),
            ];
        })->values();

        return response()->json([
            'data' => [
                'report_type' => 'attendance',
                'generated_at' => now()->toIso8601String(),
                'total_records' => $records->count(),
                'by_status' => $byStatus,
                'by_class' => $byClass,
                'records' => $records->take(100)->map(fn ($a) => [
                    'date' => $a->date?->toDateString(),
                    'student_id' => $a->student_id,
                    'student_name' => $a->student?->full_name,
                    'class_id' => $a->class_id,
                    'status' => $a->status,
                ]),
            ],
        ]);
    }

    /**
     * Financial report: aggregates from invoices, payments, transactions.
     */
    public function financial(Request $request)
    {
        $currency = $request->get('currency', 'all');

        $invoiceQuery = Invoice::query();
        $paymentQuery = Payment::where('status', 'completed');
        $txQuery = Transaction::where('status', 'completed');

        if ($currency !== 'all') {
            $invoiceQuery->where('currency', $currency);
            $paymentQuery->where('currency', $currency);
            $txQuery->where('currency', $currency);
        }
        if ($request->filled('from')) {
            $invoiceQuery->whereDate('due_date', '>=', $request->from);
            $paymentQuery->whereDate('date', '>=', $request->from);
            $txQuery->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $invoiceQuery->whereDate('due_date', '<=', $request->to);
            $paymentQuery->whereDate('date', '<=', $request->to);
            $txQuery->where('created_at', '<=', $request->to);
        }

        $invoices = $invoiceQuery->get();
        $payments = $paymentQuery->get();
        $transactions = $txQuery->get();

        $outstanding = $invoices->whereIn('status', ['pending', 'partial', 'overdue'])->sum('balance');
        $collected = $payments->sum('amount');
        $byStatus = $invoices->groupBy('status')->map(fn ($r) => $r->count())->all();

        return response()->json([
            'data' => [
                'report_type' => 'financial',
                'generated_at' => now()->toIso8601String(),
                'currency_filter' => $currency,
                'total_invoices' => $invoices->count(),
                'outstanding' => round($outstanding, 2),
                'collected' => round($collected, 2),
                'by_status' => $byStatus,
                'transactions_count' => $transactions->count(),
                'records' => $invoices->take(50)->map(fn ($i) => [
                    'invoice_number' => $i->invoice_number,
                    'student_id' => $i->student_id,
                    'amount' => $i->amount,
                    'balance' => $i->balance,
                    'status' => $i->status,
                    'currency' => $i->currency,
                ]),
            ],
        ]);
    }

    public function classReport($id)
    {
        $query = Grade::with(['student', 'teacher'])->where('class_id', $id);
        $grades = $query->get();
        $att = Attendance::where('class_id', $id)->get();

        return response()->json([
            'data' => [
                'report_type' => 'class',
                'class_id' => (int) $id,
                'generated_at' => now()->toIso8601String(),
                'grades_count' => $grades->count(),
                'attendance_count' => $att->count(),
                'by_subject' => $grades->groupBy('subject')->map(fn ($r) => $r->count())->all(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:academic-performance,attendance,financial',
            'format' => 'nullable|in:csv,json',
            'term' => 'nullable|string',
            'year' => 'nullable|integer',
            'subject' => 'nullable|string',
            'class_id' => 'nullable|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'currency' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->input('type');
        $format = $request->input('format', 'csv');

        $reportResponse = match ($type) {
            'academic-performance' => $this->academicPerformance($request),
            'attendance' => $this->attendance($request),
            'financial' => $this->financial($request),
        };

        $payload = $reportResponse->getData(true)['data'];

        $this->auditService->logExport($type, $format, $request->only([
            'term', 'year', 'subject', 'class_id', 'from', 'to', 'currency',
        ]));

        if ($format === 'json') {
            return response()->json([
                'message' => 'Report exported successfully',
                'data' => $payload,
            ]);
        }

        $rows = $this->flattenReportRows($type, $payload);
        $filename = "{$type}-".now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            if ($rows !== []) {
                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function flattenReportRows(string $type, array $payload): array
    {
        return match ($type) {
            'academic-performance' => collect($payload['records'] ?? [])->map(fn ($row) => [
                'student_id' => $row['student_id'] ?? '',
                'student_name' => $row['student_name'] ?? '',
                'subject' => $row['subject'] ?? '',
                'score' => $row['score'] ?? '',
                'total' => $row['total'] ?? '',
                'grade' => $row['grade'] ?? '',
                'term' => $row['term'] ?? '',
                'year' => $row['year'] ?? '',
            ])->all(),
            'attendance' => collect($payload['records'] ?? [])->map(fn ($row) => [
                'date' => $row['date'] ?? '',
                'student_id' => $row['student_id'] ?? '',
                'student_name' => $row['student_name'] ?? '',
                'class_id' => $row['class_id'] ?? '',
                'status' => $row['status'] ?? '',
            ])->all(),
            'financial' => collect($payload['records'] ?? [])->map(fn ($row) => [
                'invoice_number' => $row['invoice_number'] ?? '',
                'student_id' => $row['student_id'] ?? '',
                'amount' => $row['amount'] ?? '',
                'balance' => $row['balance'] ?? '',
                'status' => $row['status'] ?? '',
                'currency' => $row['currency'] ?? '',
            ])->all(),
            default => [],
        };
    }

    /**
     * Store report template (Fix 4.6).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'nullable|string|max:100',
            'recipients' => 'nullable|array',
            'recipients.*' => 'string',
            'parameters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = ReportTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'frequency' => $request->frequency,
            'recipients' => $request->recipients,
            'parameters' => $request->parameters ?? [],
            'school_id' => $request->user()?->school_id,
        ]);

        return response()->json([
            'data' => $template,
            'message' => 'Report template created successfully',
        ], 201);
    }

    public function generate($reportType, Request $request)
    {
        switch ($reportType) {
            case 'academic-performance':
                return $this->academicPerformance($request);
            case 'attendance':
                return $this->attendance($request);
            case 'financial':
                return $this->financial($request);
            default:
                return response()->json([
                    'message' => "Report type '{$reportType}' not found",
                ], 404);
        }
    }
}
