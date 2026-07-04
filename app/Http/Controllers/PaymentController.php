<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Models\School;
use App\Services\AuditService;
use App\Services\FinancialLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

        // Optimized Eager Loading
        $query = Payment::with([
            'student:id,full_name,student_number',
            'invoice:id,invoice_number,balance'
        ])->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        // Mass assignment filter safety
        foreach (['student_id', 'invoice_id', 'method', 'status', 'currency'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
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
                // Note: optimized indexes should be present for these fields
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

        // Guard against massive unpaginated queries
        if ($request->boolean('all')) {
            return PaymentResource::collection($query->limit(500)->get())
                ->additional(['message' => 'Success']);
        }

        if ($request->filled('limit') && ! $request->filled('page')) {
            $limit = min((int) $request->limit, 100); // Caps unpaginated custom limits
            return PaymentResource::collection($query->limit($limit)->get())
                ->additional(['message' => 'Success']);
        }

        $perPage = min($request->integer('per_page', 25), 100);

        return PaymentResource::collection($query->paginate($perPage))
            ->additional(['message' => 'Success']);
    }

    public function store(Request $request)
    {
        $schoolId = $request->user()->school_id;

        // Use relationship if available to save a query, or fallback safely
        $schoolCurrency = $request->user()->school?->getDefaultCurrency()
            ?? School::findOrFail($schoolId)->getDefaultCurrency();

        $invoiceRule = Rule::exists('invoices', 'id');
        if ($schoolId) {
            $invoiceRule = $invoiceRule->where('school_id', $schoolId);
        }

        $validator = Validator::make($request->all(), [
            'invoice_id' => ['required', $invoiceRule],
            'amount' => 'required|numeric|min:0.01',
            'currency' => ['nullable', 'string', Rule::in(['USD', 'ZWG'])],
            'method' => ['required', 'string', 'max:50', Rule::in(self::PAYMENT_METHODS)],
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
                method: strtolower(trim($request->filled('method'))),
                schoolId: $schoolId,
                createdBy: $request->user()->id,
                reference: $request->reference,
                notes: $request->notes,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new PaymentResource($payment))
            ->additional(['message' => 'Payment recorded successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function receipt(Request $request, Payment $payment)
    {
        // Tenant Scoping Guard
        if ($request->user()->school_id && $payment->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        $payment->load([
            'student:id,full_name,student_number,class',
            'invoice:id,invoice_number,amount,balance,description,due_date',
            'school:id,name,code,address,phone,email',
            'createdBy:id,name,first_name,last_name',
        ]);

        return response()->json([
            'data' => [
                'receipt_number' => 'RCT-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                'issued_at' => now()->toIso8601String(),
                'payment' => new PaymentResource($payment),
                'school' => $payment->school,
            ],
        ]);
    }

    public function reverse(Request $request, Payment $payment)
    {
        // Tenant Scoping Guard
        if ($request->user()->school_id && $payment->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

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

        return (new PaymentResource($payment))
            ->additional(['message' => 'Payment reversed successfully'])
            ->response();
    }

    public function destroy(Request $request, Payment $payment)
    {
        // Tenant Scoping Guard
        if ($request->user()->school_id && $payment->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

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
