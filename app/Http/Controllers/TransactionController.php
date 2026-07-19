<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $query = Transaction::with(['student', 'payroll.teacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

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

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return TransactionResource::collection($transactions)
            ->additional(['message' => 'Success']);
    }

    public function summary(Request $request)
    {
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
}
