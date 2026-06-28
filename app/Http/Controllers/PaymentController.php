<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\School;
use App\Services\AuditService;
use App\Services\FinancialLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class PaymentController extends Controller
{
    private const PAYMENT_METHODS = [
        'cash', 'ecocash', 'onemoney', 'innbucks', 'bank_transfer', 'card', 'cheque', 'other',
    ];

    public function __construct(
        private FinancialLedgerService $ledgerService,
        private AuditService $auditService,
    ) {}

    public function index(Request $request)
    {
        $schoolId = $request->user()->school_id;

        $query = Payment::with(['student:id,full_name,student_number', 'invoice:id,invoice_number,balance'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }
        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%");
                })
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('invoice_number', 'like', "%{$search}%");
                    });
            });
        }

        $sort = $request->get('sort', 'date');
        $allowedSorts = ['date', 'amount', 'created_at', 'status', 'method'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'date';
        }

        $order = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $order);

        if ($request->boolean('all')) {
            return response()->json(['data' => $query->limit(500)->get()]);
        }

        if ($request->filled('limit') && ! $request->filled('page')) {
            return response()->json(['data' => $query->limit((int) $request->limit)->get()]);
        }

        $perPage = min($request->integer('per_page', 25), 100);

        return response()->json([
            'message' => 'Success',
            'data' => $query->paginate($perPage),
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $school = School::findOrFail($schoolId);
        $schoolCurrency = $school->getDefaultCurrency();

        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|in:USD,ZWG',
            'method' => 'required|string|max:50',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency = strtoupper($request->input('currency', $schoolCurrency));
        if ($currency !== strtoupper($schoolCurrency)) {
            return response()->json([
                'message' => "Payment currency must match school currency ({$schoolCurrency})",
                'errors' => ['currency' => ["Payment must be in {$schoolCurrency}"]],
            ], 422);
        }

        try {
            $payment = $this->ledgerService->recordPayment(
                invoiceId: (int) $request->invoice_id,
                amount: (float) $request->amount,
                method: strtolower(trim($request->method)),
                schoolId: $schoolId,
                createdBy: $request->user()->id,
                reference: $request->reference,
                notes: $request->notes,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $payment,
            'message' => 'Payment recorded successfully',
        ], 201);
    }

    public function receipt($id)
    {
        $payment = Payment::with([
            'student:id,full_name,student_number,class',
            'invoice:id,invoice_number,amount,balance,description,due_date',
            'school:id,name,code,address,phone,email',
            'createdBy:id,name,first_name,last_name',
        ])->findOrFail($id);

        return response()->json([
            'data' => [
                'receipt_number' => 'RCT-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                'issued_at' => now()->toIso8601String(),
                'payment' => $payment,
                'school' => $payment->school,
            ],
        ]);
    }

    public function reverse(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payment = $this->ledgerService->reversePayment(
                $payment,
                $request->user()->id,
                $request->input('reason'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->auditService->log(
            module: 'finance',
            action: 'payment_reversed',
            auditable: $payment,
            description: "Payment #{$payment->id} reversed ({$payment->amount} {$payment->currency})",
            metadata: [
                'student_id' => $payment->student_id,
                'invoice_id' => $payment->invoice_id,
                'reason' => $request->input('reason'),
            ],
        );

        return response()->json([
            'data' => $payment,
            'message' => 'Payment reversed successfully',
        ]);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === 'completed') {
            return response()->json([
                'message' => 'Completed payments cannot be deleted. Use reverse instead.',
            ], 422);
        }

        $payment->delete();

        return response()->json([
            'message' => 'Payment deleted successfully',
        ]);
    }
}
