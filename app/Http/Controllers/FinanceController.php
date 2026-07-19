<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\School;
use App\Models\Student;
use App\Models\Transaction;
use App\Services\AuditService;
use App\Services\CashFlowService;
use App\Services\FinancialLedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private FinancialLedgerService $ledgerService,
        private CashFlowService $cashFlowService,
    ) {}

    public function summary(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $school = $schoolId ? School::find($schoolId) : null;
        $currency = strtoupper($request->get('currency', $school?->getDefaultCurrency() ?? 'USD'));

        if ($schoolId) {
            $this->ledgerService->syncOverdueInvoices($schoolId);
        }

        $invoiceQuery = Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency);

        $outstandingFees = (clone $invoiceQuery)
            ->where('balance', '>', 0)
            ->sum('balance');

        $collectedToday = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereDate('date', today())
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->sum('amount');

        $totalRevenue = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->sum('amount');

        $totalInvoices = (clone $invoiceQuery)->count();
        $pendingInvoices = (clone $invoiceQuery)->where('status', 'pending')->count();
        $overdueInvoices = (clone $invoiceQuery)->where('status', 'overdue')->count();
        $partialInvoices = (clone $invoiceQuery)->where('status', 'partial')->count();

        $payrollExpenseQuery = Transaction::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('type', 'expense')
            ->where('category', 'payroll')
            ->where('currency', $currency);

        $payrollPaidToday = (clone $payrollExpenseQuery)
            ->whereDate('created_at', today())
            ->sum('debit');

        $payrollPaidThisMonth = (clone $payrollExpenseQuery)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('debit');

        $payrollPending = Payroll::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', ['pending', 'partial'])
            ->where('currency', $currency)
            ->get()
            ->sum(fn (Payroll $row) => max(0, (float) $row->net_salary - (float) $row->amount_paid));

        return response()->json([
            'data' => [
                'totalOutstanding' => round((float) $outstandingFees, 2),
                'collectedToday' => round((float) $collectedToday, 2),
                'totalRevenue' => round((float) $totalRevenue, 2),
                'payrollPaidToday' => round((float) $payrollPaidToday, 2),
                'payrollPaidThisMonth' => round((float) $payrollPaidThisMonth, 2),
                'payrollPending' => round((float) $payrollPending, 2),
                'netCashToday' => round((float) $collectedToday - (float) $payrollPaidToday, 2),
                'totalInvoices' => $totalInvoices,
                'pendingInvoices' => $pendingInvoices,
                'partialInvoices' => $partialInvoices,
                'overdueInvoices' => $overdueInvoices,
                'currency' => $currency,
            ],
        ]);
    }

    public function outstandingBalances(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $query = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('balance', '>', 0)
            ->with(['classModel:id,name']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('balance');

        if ($request->boolean('all')) {
            $students = $query->limit(500)->get();
        } else {
            $perPage = min($request->integer('per_page', 25), 100);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'message' => 'Success',
                'data' => [
                    'total_outstanding' => round((float) Student::query()
                        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                        ->where('balance', '>', 0)
                        ->sum('balance'), 2),
                    'students' => $paginator,
                ],
            ]);
        }

        $mapped = $students->map(fn (Student $student) => [
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'full_name' => $student->full_name,
            'class' => $student->classModel?->name ?? $student->class,
            'balance' => (float) $student->balance,
            'currency' => $student->currency,
        ]);

        return response()->json([
            'data' => [
                'total_outstanding' => round($mapped->sum('balance'), 2),
                'students' => $mapped,
            ],
        ]);
    }

    /**
     * Aging report — outstanding invoice balances grouped by days past due.
     */
    public function aging(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $today = now()->startOfDay();

        if ($schoolId) {
            $this->ledgerService->syncOverdueInvoices($schoolId);
        }

        $rows = Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('balance', '>', 0)
            ->select([
                'id',
                'invoice_number',
                'student_id',
                'amount',
                'balance',
                'currency',
                'due_date',
                'status',
            ])
            ->with(['student:id,full_name,student_number'])
            ->orderBy('due_date')
            ->limit(2000)
            ->get()
            ->map(function (Invoice $invoice) use ($today) {
                $daysPastDue = 0;
                if ($invoice->due_date) {
                    $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
                    if ($dueDate->lessThanOrEqualTo($today)) {
                        $daysPastDue = (int) $dueDate->diffInDays($today);
                    }
                }

                $bucket = match (true) {
                    $daysPastDue === 0 => 'current',
                    $daysPastDue <= 30 => '1_30',
                    $daysPastDue <= 60 => '31_60',
                    $daysPastDue <= 90 => '61_90',
                    default => '90_plus',
                };

                return [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'student_id' => $invoice->student_id,
                    'student_name' => $invoice->student?->full_name,
                    'student_number' => $invoice->student?->student_number,
                    'balance' => (float) $invoice->balance,
                    'currency' => $invoice->currency,
                    'due_date' => $invoice->due_date?->toDateString(),
                    'days_past_due' => $daysPastDue,
                    'bucket' => $bucket,
                    'status' => $invoice->status,
                ];
            });

        $buckets = [
            'current' => ['label' => 'Current', 'total' => 0, 'count' => 0],
            '1_30' => ['label' => '1–30 days', 'total' => 0, 'count' => 0],
            '31_60' => ['label' => '31–60 days', 'total' => 0, 'count' => 0],
            '61_90' => ['label' => '61–90 days', 'total' => 0, 'count' => 0],
            '90_plus' => ['label' => '90+ days', 'total' => 0, 'count' => 0],
        ];

        foreach ($rows as $row) {
            $bucket = $row['bucket'];
            $buckets[$bucket]['total'] += $row['balance'];
            $buckets[$bucket]['count']++;
        }

        foreach ($buckets as $key => $bucket) {
            $buckets[$key]['total'] = round($bucket['total'], 2);
        }

        $this->auditService->log(
            module: 'finance',
            action: 'aging_report_view',
            description: 'Viewed accounts receivable aging report',
        );

        return response()->json([
            'data' => [
                'generated_at' => now()->toIso8601String(),
                'total_outstanding' => round($rows->sum('balance'), 2),
                'buckets' => $buckets,
                'invoices' => $rows->values(),
            ],
        ]);
    }

    public function reconciliation(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $period = $request->input('period', 'daily');
        [$from, $to] = $this->resolvePeriodRange($period, $request);

        $paymentsQuery = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereBetween('date', [$from, $to])
            ->where('status', 'completed');

        $byMethod = (clone $paymentsQuery)
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'count' => (int) $row->count,
                'total' => (float) $row->total,
            ]);

        $reversed = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereBetween('updated_at', [$from, $to->copy()->endOfDay()])
            ->where('status', 'reversed')
            ->sum('amount');

        $invoiced = Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->sum('amount');

        $collected = (clone $paymentsQuery)->sum('amount');

        $payrollPaid = Transaction::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('type', 'expense')
            ->where('category', 'payroll')
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->sum('debit');

        $this->auditService->log(
            module: 'finance',
            action: 'reconciliation_view',
            description: "Viewed {$period} reconciliation report",
            metadata: ['period' => $period, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
        );

        return response()->json([
            'data' => [
                'period' => $period,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'invoiced' => round((float) $invoiced, 2),
                'collected' => round((float) $collected, 2),
                'reversed' => round((float) $reversed, 2),
                'net_collected' => round((float) $collected - (float) $reversed, 2),
                'payroll_paid' => round((float) $payrollPaid, 2),
                'net_cash' => round((float) $collected - (float) $reversed - (float) $payrollPaid, 2),
                'variance' => round((float) $collected - (float) $reversed, 2),
                'by_payment_method' => $byMethod,
                'cash_total' => round((float) $byMethod->where('method', 'cash')->sum('total'), 2),
                'bank_total' => round((float) $byMethod->filter(fn ($m) => in_array(strtolower($m['method']), ['bank', 'bank_transfer', 'eft'], true))->sum('total'), 2),
                'mobile_money_total' => round((float) $byMethod->filter(fn ($m) => in_array(strtolower($m['method']), ['mobile', 'mobile_money', 'ecocash', 'onemoney', 'innbucks'], true))->sum('total'), 2),
            ],
        ]);
    }

    public function periodReport(Request $request, string $period)
    {
        $request->merge(['period' => $period]);

        return $this->reconciliation($request);
    }

    /**
     * Full cash 360: money in, money out, net, and ledger balance check.
     */
    public function cashFlow(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $school = $schoolId ? School::find($schoolId) : null;
        $currency = strtoupper($request->get('currency', $school?->getDefaultCurrency() ?? 'USD'));
        $period = $request->input('period', 'monthly');
        [$from, $to] = $this->resolvePeriodRange($period, $request);

        $report = $this->cashFlowService->report($schoolId, $currency, $from, $to, $period);

        $this->auditService->log(
            module: 'finance',
            action: 'cash_flow_view',
            description: "Viewed {$period} cash flow 360 report",
            metadata: [
                'period' => $period,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'net_cash' => $report['net_cash'],
                'is_balanced' => $report['balance_check']['is_balanced'],
            ],
        );

        return response()->json(['data' => $report]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolvePeriodRange(string $period, Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            return [Carbon::parse($request->from)->startOfDay(), Carbon::parse($request->to)->endOfDay()];
        }

        return match ($period) {
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'term' => [now()->startOfMonth()->subMonths(2), now()->endOfMonth()],
            'ytd' => [now()->startOfYear(), now()->endOfDay()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }
}
