<?php

namespace App\Http\Controllers;

use App\Http\Concerns\HandlesResourceQueries;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Models\School;
use App\Services\AuditService;
use App\Services\FinancialLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;

class PaymentController extends Controller
{
    use HandlesResourceQueries;

    private const PAYMENT_METHODS = [
        'cash', 'ecocash', 'onemoney', 'innbucks', 'bank_transfer', 'card', 'cheque', 'other',
    ];

    public function __construct(
        private FinancialLedgerService $ledgerService,
        private AuditService $auditService,
    ) {}

    public function index(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $schoolId = $request->user()->school_id;

        $base = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        return $this->paginateResource($request, Payment::class, PaymentResource::class, [
            'base' => $base,
            'filters' => [
                'student_id',
                'invoice_id',
                'method',
                'status',
                'currency',
                AllowedFilter::callback('from', fn ($query, $value) => $query->whereDate('date', '>=', $value)),
                AllowedFilter::callback('to', fn ($query, $value) => $query->whereDate('date', '<=', $value)),
                'search',
            ],
            'search_columns' => [
                'reference',
                'student.full_name',
                'student.student_number',
                'invoice.invoice_number',
            ],
            'sorts' => ['date', 'amount', 'created_at', 'status', 'method'],
            'includes' => ['student', 'invoice', 'createdBy'],
            'default_sort' => '-date',
            'with' => [
                'student:id,full_name,student_number',
                'invoice:id,invoice_number,balance',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

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
                method: strtolower(trim((string) $request->input('method'))),
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
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

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
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

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
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

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
