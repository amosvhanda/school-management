<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\PayrollPayslipResource;
use App\Http\Resources\Api\V1\PayrollResource;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\Payroll;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    /**
     * List payroll records with optional filters: month, year, status.
     * Returns payroll with teacher details, allowances, and deductions breakdown.
     */
    public function index(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $query = Payroll::with('teacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('month')) {
            $query->where('month', (int) $request->month);
        }
        if ($request->filled('year')) {
            $query->where('year', (int) $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rows = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('teacher_id')
            ->get();

        return PayrollResource::collection($rows)
            ->additional(['message' => 'Success']);
    }

    /**
     * Get all teachers available for payroll assignment
     */
    public function getTeachers(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $teachers = Teacher::where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->select('id', 'name', 'employee_id', 'department', 'base_salary', 'salary_currency', 'allowances', 'deductions')
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'employee_id' => $teacher->employee_id,
                    'department' => $teacher->department ?? 'Academic',
                    'base_salary' => (float) ($teacher->base_salary ?? 0),
                    'salary_currency' => $teacher->salary_currency ?? 'USD',
                    'allowances' => $teacher->allowances ?? [],
                    'deductions' => $teacher->deductions ?? [],
                ];
            });

        return response()->json(['data' => $teachers]);
    }

    /**
     * Generate/Assign payroll for given month/year and teacher_ids.
     * Automatically calculates from teacher's salary settings.
     */
    public function generate(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $teacherRule = Rule::exists('teachers', 'id');
        if ($schoolId) {
            $teacherRule = $teacherRule->where('school_id', $schoolId);
        }

        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => $teacherRule,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $month = (int) $request->month;
        $year = (int) $request->year;
        $schoolId = $request->user()?->school_id ?? School::first()?->id;

        if (! $schoolId) {
            return response()->json(['message' => 'No school context'], 400);
        }

        $teacherIds = $request->input('teacher_ids', []);
        if (empty($teacherIds)) {
            $teacherIds = Teacher::query()
                ->where('school_id', $schoolId)
                ->where(function ($q) {
                    $q->where('status', 'active')->orWhereNull('status');
                })
                ->pluck('id')
                ->all();
        }

        if (empty($teacherIds)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['teacher_ids' => ['No active teachers available to generate payroll for.']],
            ], 422);
        }

        $created = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($teacherIds as $tid) {
                $teacher = Teacher::where('school_id', $schoolId)->find($tid);
                if (! $teacher) {
                    $errors[] = "Teacher ID {$tid} not found or not part of the current school";

                    continue;
                }

                // Get base salary from teacher record
                $baseSalary = $teacher->base_salary ?? 0;
                $periods = (int) $request->input('periods', 0);

                if (($teacher->employment_type ?? 'full_time') === 'part_time' && $periods > 0) {
                    $rate = (float) ($teacher->period_rate ?? 0);
                    if ($rate <= 0) {
                        $errors[] = "Part-time teacher {$teacher->name} has no period rate assigned";

                        continue;
                    }
                    $baseSalary = $rate * $periods;
                } elseif ($baseSalary <= 0) {
                    $errors[] = "Teacher {$teacher->name} has no base salary assigned";

                    continue;
                }

                // Get allowances and deductions from teacher record or use defaults
                $allowances = $teacher->allowances ?? [];
                $deductions = $teacher->deductions ?? [];

                // Calculate totals
                $allowancesTotal = is_array($allowances)
                    ? array_sum(array_values($allowances))
                    : 0;
                $deductionsTotal = is_array($deductions)
                    ? array_sum(array_values($deductions))
                    : 0;

                $grossSalary = $baseSalary + $allowancesTotal;
                $netSalary = $grossSalary - $deductionsTotal;

                $currency = $teacher->salary_currency ?? 'USD';

                // Check if payroll already exists
                $existing = Payroll::where('school_id', $schoolId)
                    ->where('teacher_id', $tid)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                if ($existing) {
                    // Update existing payroll
                    $existing->update([
                        'base_salary' => $baseSalary,
                        'allowances' => $allowances,
                        'allowances_total' => $allowancesTotal,
                        'gross_salary' => $grossSalary,
                        'deductions' => $deductions,
                        'deductions_total' => $deductionsTotal,
                        'net_salary' => $netSalary,
                        'currency' => $currency,
                        'status' => $existing->amount_paid >= $netSalary ? 'paid' : ($existing->amount_paid > 0 ? 'partial' : 'pending'),
                    ]);
                    $created++;
                } else {
                    // Create new payroll
                    Payroll::create([
                        'school_id' => $schoolId,
                        'teacher_id' => $tid,
                        'month' => $month,
                        'year' => $year,
                        'base_salary' => $baseSalary,
                        'allowances' => $allowances,
                        'allowances_total' => $allowancesTotal,
                        'gross_salary' => $grossSalary,
                        'deductions' => $deductions,
                        'deductions_total' => $deductionsTotal,
                        'net_salary' => $netSalary,
                        'amount_paid' => 0,
                        'currency' => $currency,
                        'status' => 'pending',
                    ]);
                    $created++;
                }
            }

            DB::commit();

            $message = "Payroll generated for {$created} teacher(s).";
            if (! empty($errors)) {
                $message .= ' Errors: '.implode(', ', $errors);
            }

            return response()->json([
                'message' => $message,
                'data' => [
                    'created' => $created,
                    'errors' => $errors,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payroll generation failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to generate payroll',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update payroll record (for manual adjustments)
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'base_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|array',
            'deductions' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schoolId = $request->user()?->school_id;
        $payroll = Payroll::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            if ($request->has('base_salary')) {
                $payroll->base_salary = $request->base_salary;
            }
            if ($request->has('allowances')) {
                $payroll->allowances = $request->allowances;
                $payroll->allowances_total = array_sum(array_values($request->allowances));
            }
            if ($request->has('deductions')) {
                $payroll->deductions = $request->deductions;
                $payroll->deductions_total = array_sum(array_values($request->deductions));
            }
            if ($request->has('notes')) {
                $payroll->notes = $request->notes;
            }

            // Recalculate
            $payroll->gross_salary = $payroll->base_salary + $payroll->allowances_total;
            $payroll->net_salary = $payroll->gross_salary - $payroll->deductions_total;
            if ($payroll->amount_paid >= $payroll->net_salary) {
                $payroll->status = 'paid';
            } elseif ($payroll->amount_paid > 0) {
                $payroll->status = 'partial';
            } else {
                $payroll->status = 'pending';
            }
            $payroll->save();

            DB::commit();

            return (new PayrollResource($payroll->fresh('teacher')))
                ->additional(['message' => 'Payroll updated successfully'])
                ->response();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update payroll',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process payment for a payroll record.
     * This marks the payroll as paid and creates an expense transaction.
     */
    public function process(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:bank_transfer,cash,ecocash,onemoney,zipit,swipe',
            'payment_reference' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'amount_paid' => 'nullable|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schoolId = $request->user()?->school_id;
        $payroll = Payroll::with('teacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        if ($payroll->status === 'paid') {
            return response()->json([
                'message' => 'Payroll already paid',
            ], 400);
        }

        $schoolId = $payroll->school_id;
        $user = $request->user();

        DB::beginTransaction();
        try {
            $remaining = (float) $payroll->net_salary - (float) $payroll->amount_paid;
            $paymentAmount = (float) ($request->input('amount_paid', $remaining));
            if ($paymentAmount <= 0) {
                return response()->json([
                    'message' => 'Payment amount must be greater than zero',
                ], 422);
            }
            if ($paymentAmount > $remaining) {
                return response()->json([
                    'message' => 'Payment amount exceeds remaining balance',
                ], 422);
            }

            // Update payroll status
            $payroll->amount_paid = (float) $payroll->amount_paid + $paymentAmount;
            $payroll->status = $payroll->amount_paid >= $payroll->net_salary ? 'paid' : 'partial';
            $payroll->paid_at = $payroll->status === 'paid' ? ($request->paid_at ?? now()) : $payroll->paid_at;
            $payroll->payment_method = $request->payment_method;
            $payroll->payment_reference = $request->payment_reference;
            $payroll->processed_by = $user->id;
            $payroll->save();

            // Create expense transaction (deducts from school income)
            $transaction = Transaction::create([
                'school_id' => $schoolId,
                'payroll_id' => $payroll->id,
                'student_id' => null, // Payroll expenses don't have student_id
                'type' => 'expense',
                'category' => 'payroll',
                'description' => "Payroll payment for {$payroll->teacher->name} - {$payroll->month}/{$payroll->year}",
                'reference' => $request->payment_reference ?? "PAYROLL-{$payroll->id}",
                'debit' => $paymentAmount, // Expense (money going out)
                'credit' => 0,
                'balance' => -$paymentAmount, // Negative balance for expenses
                'currency' => $payroll->currency,
                'status' => 'completed',
                'payment_method' => $request->payment_method,
                'created_by' => $user->id,
                'notes' => "Payroll payment for employee {$payroll->teacher->employee_id}",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Payroll processed and payment recorded successfully',
                'data' => [
                    'payroll' => new PayrollResource($payroll->fresh('teacher')),
                    'transaction' => new TransactionResource($transaction),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payroll processing failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to process payroll',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate payslip for a payroll record
     */
    public function payslip($id)
    {
        $schoolId = request()->user()?->school_id;
        $payroll = Payroll::with(['teacher', 'school'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        return (new PayrollPayslipResource($payroll))
            ->additional(['message' => 'Success'])
            ->response();
    }

    /**
     * Get payroll summary/statistics
     */
    public function summary(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $baseQuery = Payroll::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('month')) {
            $baseQuery->where('month', (int) $request->month);
        }
        if ($request->filled('year')) {
            $baseQuery->where('year', (int) $request->year);
        }

        $totalPayroll = (clone $baseQuery)->sum('gross_salary');
        $totalPaid = (clone $baseQuery)->where('status', 'paid')->sum('net_salary');
        $totalPending = (clone $baseQuery)->where('status', 'pending')->sum('net_salary');
        $totalPartial = (clone $baseQuery)->where('status', 'partial')->sum('net_salary');
        $totalEmployees = (clone $baseQuery)->count();
        $paidEmployees = (clone $baseQuery)->where('status', 'paid')->count();
        $pendingEmployees = (clone $baseQuery)->where('status', 'pending')->count();
        $partialEmployees = (clone $baseQuery)->where('status', 'partial')->count();

        return response()->json([
            'data' => [
                'total_payroll' => (float) $totalPayroll,
                'total_paid' => (float) $totalPaid,
                'total_pending' => (float) $totalPending,
                'total_partial' => (float) $totalPartial,
                'total_employees' => $totalEmployees,
                'paid_employees' => $paidEmployees,
                'pending_employees' => $pendingEmployees,
                'partial_employees' => $partialEmployees,
            ],
        ]);
    }

    /**
     * Get payroll history for a specific teacher, including payments.
     */
    public function teacherHistory(Request $request, $teacherId)
    {
        $schoolId = $request->user()?->school_id;

        $teacher = Teacher::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($teacherId);

        $records = Payroll::with(['teacher', 'transactions'])
            ->where('teacher_id', $teacherId)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return PayrollResource::collection($records)
            ->additional(['message' => 'Success'])
            ->response();
    }

    /**
     * Payroll trends by month.
     */
    public function trends(Request $request)
    {
        $months = (int) $request->get('months', 6);
        $schoolId = $request->user()?->school_id;

        $data = collect(range(0, $months - 1))->map(function ($offset) use ($schoolId) {
            $date = now()->subMonths($offset);
            $month = (int) $date->format('n');
            $year = (int) $date->format('Y');

            $query = Payroll::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('month', $month)
                ->where('year', $year);

            return [
                'month' => $date->format('M'),
                'year' => $year,
                'totalPayroll' => (float) $query->sum('gross_salary'),
                'employees' => $query->count(),
            ];
        })->reverse()->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Payroll totals grouped by department for a month/year.
     */
    public function departmentSummary(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $records = Payroll::with('teacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $summary = $records->groupBy(fn ($p) => $p->teacher?->department ?? 'Unassigned')
            ->map(function ($rows, $department) {
                return [
                    'name' => $department,
                    'value' => (float) $rows->sum('net_salary'),
                ];
            })
            ->values();

        return response()->json([
            'data' => $summary,
        ]);
    }

    /**
     * Map internal status to frontend-friendly status
     */
    private function mapStatus($status, float $amountPaid = 0, float $netSalary = 0)
    {
        if ($status === 'paid' || ($netSalary > 0 && $amountPaid >= $netSalary)) {
            return 'Paid';
        }
        if ($status === 'partial' || ($amountPaid > 0 && $netSalary > $amountPaid)) {
            return 'Partial';
        }
        if ($status === 'cancelled') {
            return 'Cancelled';
        }

        return 'Unpaid';
    }
}
