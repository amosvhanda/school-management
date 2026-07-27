<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\ExpenseHead;
use App\Models\IncomeHead;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $schoolId = $request->user()?->school_id;
        $query = Transaction::with(['student', 'payroll.teacher', 'incomeHead', 'expenseHead'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->boolean('manual_only')) {
            $query->whereNull('payment_id')
                ->whereNull('payroll_id')
                ->whereNull('invoice_id');
        }

        if ($request->filled('student_id') && $request->student_id !== 'all') {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('payroll_id') && $request->payroll_id !== 'all') {
            $query->where('payroll_id', $request->payroll_id);
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        if ($request->filled('currency') && $request->currency !== 'all') {
            $query->where('currency', $request->currency);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('income_head_id')) {
            $query->where('income_head_id', $request->integer('income_head_id'));
        }
        if ($request->filled('expense_head_id')) {
            $query->where('expense_head_id', $request->integer('expense_head_id'));
        }
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('payroll.teacher', function ($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->boolean('all')) {
            $transactions = $query->orderBy('created_at', 'desc')->limit(500)->get();

            return TransactionResource::collection($transactions)
                ->additional(['message' => 'Success']);
        }

        if ($request->filled('limit') && ! $request->filled('page') && ! $request->filled('per_page')) {
            $limit = min(max((int) $request->input('limit'), 1), 200);
            $transactions = $query->orderBy('created_at', 'desc')->limit($limit)->get();

            return TransactionResource::collection($transactions)
                ->additional(['message' => 'Success']);
        }

        $perPage = min(max($request->integer('per_page', 25), 1), 100);
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage)->appends($request->query());

        return TransactionResource::collection($paginator)
            ->additional(['message' => 'Success']);
    }

    public function summary(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $schoolId = $request->user()?->school_id;
        $query = Transaction::where('status', 'completed')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $transactions = $query->get();
        $payrollPaid = $transactions
            ->where('type', 'expense')
            ->where('category', 'payroll')
            ->sum('debit');

        $summary = [
            'total_debit_usd' => $transactions->where('currency', 'USD')->sum('debit'),
            'total_credit_usd' => $transactions->where('currency', 'USD')->sum('credit'),
            'total_debit_zwl' => $transactions->where('currency', 'ZWL')->sum('debit'),
            'total_credit_zwl' => $transactions->where('currency', 'ZWL')->sum('credit'),
            'net_balance_usd' => $transactions->where('currency', 'USD')->sum('debit') - $transactions->where('currency', 'USD')->sum('credit'),
            'net_balance_zwl' => $transactions->where('currency', 'ZWL')->sum('debit') - $transactions->where('currency', 'ZWL')->sum('credit'),
            'payroll_paid_usd' => (float) $transactions->where('currency', 'USD')->where('type', 'expense')->where('category', 'payroll')->sum('debit'),
            'payroll_paid_zwl' => (float) $transactions->where('currency', 'ZWL')->where('type', 'expense')->where('category', 'payroll')->sum('debit'),
            'payroll_paid' => (float) $payrollPaid,
            'total_transactions' => $transactions->count(),
        ];

        return response()->json([
            'data' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.manage'],
        );

        $user = $request->user();
        $schoolId = $user?->school_id;

        $data = $this->validateEntry($request, $schoolId);

        $transaction = new Transaction($this->attributesFromEntry($data, $schoolId));
        $transaction->created_by = $user?->id;
        if (! empty($data['date'])) {
            $transaction->created_at = $data['date'];
        }
        $transaction->save();

        return (new TransactionResource($transaction->load(['incomeHead', 'expenseHead'])))
            ->additional(['message' => 'Transaction recorded'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.manage'],
        );

        $schoolId = $request->user()?->school_id;

        $transaction = Transaction::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        if ($response = $this->guardSystemGenerated($transaction)) {
            return $response;
        }

        $data = $this->validateEntry($request, $schoolId);

        $transaction->fill($this->attributesFromEntry($data, $schoolId));
        if (! empty($data['date'])) {
            $transaction->created_at = $data['date'];
        }
        $transaction->save();

        return (new TransactionResource($transaction->fresh()->load(['incomeHead', 'expenseHead'])))
            ->additional(['message' => 'Transaction updated']);
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.manage'],
        );

        $schoolId = $request->user()?->school_id;

        $transaction = Transaction::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        if ($response = $this->guardSystemGenerated($transaction)) {
            return $response;
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEntry(Request $request, ?int $schoolId): array
    {
        return $request->validate([
            'type' => ['required', 'in:income,expense'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'income_head_id' => [
                'required_if:type,income',
                'nullable',
                'integer',
                Rule::exists('income_heads', 'id')->where(fn ($q) => $q->when(
                    $schoolId,
                    fn ($inner) => $inner->where('school_id', $schoolId),
                )),
            ],
            'expense_head_id' => [
                'required_if:type,expense',
                'nullable',
                'integer',
                Rule::exists('expense_heads', 'id')->where(fn ($q) => $q->when(
                    $schoolId,
                    fn ($inner) => $inner->where('school_id', $schoolId),
                )),
            ],
            'currency' => ['nullable', 'string', 'size:3'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFromEntry(array $data, ?int $schoolId): array
    {
        $amount = round((float) $data['amount'], 2);
        $isIncome = $data['type'] === 'income';

        $attributes = [
            'school_id' => $schoolId,
            'type' => $data['type'],
            'description' => $data['description'],
            'reference' => $data['reference'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'status' => 'completed',
            'payment_method' => $data['payment_method'] ?? null,
            'notes' => $data['notes'] ?? null,
            'income_head_id' => null,
            'expense_head_id' => null,
        ];

        if ($isIncome) {
            $head = IncomeHead::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->findOrFail($data['income_head_id']);
            $attributes['income_head_id'] = $head->id;
            $attributes['category'] = $head->name;
            $attributes['credit'] = $amount;
            $attributes['debit'] = 0;
            $attributes['balance'] = $amount;
        } else {
            $head = ExpenseHead::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->findOrFail($data['expense_head_id']);
            $attributes['expense_head_id'] = $head->id;
            $attributes['category'] = $head->name;
            $attributes['debit'] = $amount;
            $attributes['credit'] = 0;
            $attributes['balance'] = -$amount;
        }

        return $attributes;
    }

    private function guardSystemGenerated(Transaction $transaction): ?\Illuminate\Http\JsonResponse
    {
        if ($transaction->payment_id || $transaction->payroll_id || $transaction->invoice_id) {
            return response()->json([
                'message' => 'System-generated transactions cannot be modified.',
            ], 422);
        }

        return null;
    }
}
