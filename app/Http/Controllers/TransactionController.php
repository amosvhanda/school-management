<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('student');

        if ($request->has('student_id') && $request->student_id !== 'all') {
            $query->where('student_id', $request->student_id);
        }
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->has('currency') && $request->currency !== 'all') {
            $query->where('currency', $request->currency);
        }
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        // Support search parameter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('student', function($sq) use ($search) {
                      $sq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('student_number', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $transactions,
        ]);
    }

    public function summary(Request $request)
    {
        $query = Transaction::where('status', 'completed');

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $transactions = $query->get();

        $summary = [
            'total_debit_usd' => $transactions->where('currency', 'USD')->sum('debit'),
            'total_credit_usd' => $transactions->where('currency', 'USD')->sum('credit'),
            'total_debit_zwl' => $transactions->where('currency', 'ZWL')->sum('debit'),
            'total_credit_zwl' => $transactions->where('currency', 'ZWL')->sum('credit'),
            'net_balance_usd' => $transactions->where('currency', 'USD')->sum('debit') - $transactions->where('currency', 'USD')->sum('credit'),
            'net_balance_zwl' => $transactions->where('currency', 'ZWL')->sum('debit') - $transactions->where('currency', 'ZWL')->sum('credit'),
            'total_transactions' => $transactions->count(),
        ];

        return response()->json([
            'data' => $summary,
        ]);
    }
}
