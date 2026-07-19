<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Services\FinancialLedgerService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProcurementController extends Controller
{
    public function __construct(
        private WorkflowService $workflows,
        private FinancialLedgerService $ledger,
    ) {}

    public function requisitions(Request $request)
    {
        $query = PurchaseRequisition::query()
            ->where('school_id', $request->user()->school_id)
            ->with(['requester:id,name', 'items', 'vendor:id,name', 'workflowInstance']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('spend_type')) {
            $query->where('spend_type', $request->string('spend_type'));
        }

        return response()->json(['data' => $query->orderByDesc('created_at')->get()]);
    }

    public function storeRequisition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'spend_type' => ['nullable', Rule::in(PurchaseRequisition::SPEND_TYPES)],
            'department_id' => 'nullable|exists:departments,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'submit' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $estimated = collect($request->items)->sum(fn ($i) => $i['quantity'] * $i['unit_cost']);

        $requisition = DB::transaction(function () use ($request, $user, $estimated) {
            $req = PurchaseRequisition::create([
                'school_id' => $user->school_id,
                'requested_by' => $user->id,
                'department_id' => $request->department_id,
                'vendor_id' => $request->vendor_id,
                'title' => $request->title,
                'description' => $request->description,
                'spend_type' => $request->input('spend_type', 'procurement'),
                'estimated_cost' => $estimated,
                'status' => $request->boolean('submit') ? 'pending_approval' : 'draft',
            ]);

            foreach ($request->items as $item) {
                $req->items()->create($item);
            }

            if ($request->boolean('submit')) {
                $instance = $this->workflows->start('purchase_request', $req, $user, [
                    'title' => $req->title,
                    'spend_type' => $req->spend_type,
                    'estimated_cost' => $req->estimated_cost,
                ]);
                $req->update(['workflow_instance_id' => $instance->id]);
            }

            return $req->load('items', 'workflowInstance', 'vendor:id,name');
        });

        return response()->json([
            'data' => $requisition,
            'message' => $request->boolean('submit')
                ? 'Spend request submitted for approval'
                : 'Draft spend request saved',
        ], 201);
    }

    public function updateRequisition(Request $request, int $id)
    {
        $requisition = PurchaseRequisition::where('school_id', $request->user()->school_id)->findOrFail($id);

        if ($requisition->status !== 'draft') {
            return response()->json(['message' => 'Only draft requests can be edited'], 422);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'spend_type' => ['sometimes', Rule::in(PurchaseRequisition::SPEND_TYPES)],
            'department_id' => 'nullable|exists:departments,id',
            'vendor_id' => 'nullable|exists:vendors,id',
        ]);

        $requisition->update($data);

        return response()->json([
            'data' => $requisition->fresh()->load(['items', 'requester:id,name', 'vendor:id,name']),
            'message' => 'Request updated',
        ]);
    }

    /**
     * Submit a draft for approval workflow.
     */
    public function submit(Request $request, int $id)
    {
        $user = $request->user();
        $requisition = PurchaseRequisition::where('school_id', $user->school_id)->findOrFail($id);

        if ($requisition->status !== 'draft') {
            return response()->json(['message' => 'Only draft requests can be submitted'], 422);
        }

        if ($requisition->items()->count() < 1) {
            return response()->json(['message' => 'Add at least one line item before submitting'], 422);
        }

        $requisition = DB::transaction(function () use ($requisition, $user) {
            $instance = $this->workflows->start('purchase_request', $requisition, $user, [
                'title' => $requisition->title,
                'spend_type' => $requisition->spend_type,
                'estimated_cost' => $requisition->estimated_cost,
            ]);

            $requisition->update([
                'status' => 'pending_approval',
                'workflow_instance_id' => $instance->id,
            ]);

            return $requisition->fresh()->load(['items', 'workflowInstance', 'requester:id,name']);
        });

        return response()->json([
            'data' => $requisition,
            'message' => 'Spend request submitted for approval',
        ]);
    }

    /**
     * Record payment after approval — posts an expense to the school ledger.
     */
    public function disburse(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:bank_transfer,cash,ecocash,onemoney,zipit,swipe,cheque',
            'payment_reference' => 'nullable|string|max:255',
            'amount_paid' => 'nullable|numeric|min:0.01',
            'vendor_id' => 'nullable|exists:vendors,id',
            'paid_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $requisition = PurchaseRequisition::where('school_id', $user->school_id)
            ->with('items')
            ->findOrFail($id);

        if ($requisition->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved spend requests can be paid. Current status: '.$requisition->status,
            ], 422);
        }

        if ($requisition->transaction_id) {
            return response()->json(['message' => 'This request has already been paid'], 422);
        }

        $amount = round((float) ($request->input('amount_paid', $requisition->estimated_cost)), 2);
        if ($amount <= 0) {
            return response()->json(['message' => 'Payment amount must be greater than zero'], 422);
        }

        $currency = $this->ledger->schoolCurrency((int) $user->school_id);

        $result = DB::transaction(function () use ($request, $user, $requisition, $amount, $currency) {
            if ($request->filled('vendor_id')) {
                $requisition->vendor_id = $request->vendor_id;
            }

            $transaction = $this->ledger->recordExpense(
                schoolId: (int) $user->school_id,
                amount: $amount,
                currency: $currency,
                category: $requisition->ledgerCategory(),
                description: "Spend: {$requisition->title}",
                paymentMethod: $request->payment_method,
                reference: $request->payment_reference ?? "SPEND-{$requisition->id}",
                createdBy: $user->id,
                notes: "requisition_id:{$requisition->id}; spend_type:{$requisition->spend_type}",
            );

            $requisition->update([
                'status' => 'disbursed',
                'amount_paid' => $amount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'disbursed_at' => $request->paid_at ?? now(),
                'disbursed_by' => $user->id,
                'transaction_id' => $transaction->id,
                'vendor_id' => $requisition->vendor_id,
            ]);

            return $requisition->fresh()->load(['items', 'requester:id,name', 'vendor:id,name', 'transaction']);
        });

        return response()->json([
            'data' => $result,
            'message' => 'Payment recorded and posted to the ledger',
        ]);
    }

    public function vendors(Request $request)
    {
        $query = Vendor::where('school_id', $request->user()->school_id);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function storeVendor(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $vendor = Vendor::create([...$data, 'school_id' => $request->user()->school_id]);

        return response()->json(['data' => $vendor], 201);
    }

    public function updateVendor(Request $request, int $id)
    {
        $vendor = Vendor::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $vendor->update($data);

        return response()->json(['data' => $vendor->fresh(), 'message' => 'Vendor updated']);
    }

    public function receiveGoods(Request $request)
    {
        $data = $request->validate([
            'requisition_id' => 'required|exists:purchase_requisitions,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $requisition = PurchaseRequisition::where('school_id', $request->user()->school_id)
            ->findOrFail($data['requisition_id']);

        if (! in_array($requisition->status, ['approved', 'disbursed'], true)) {
            return response()->json([
                'message' => 'Goods can only be received after the spend request is approved or paid',
            ], 422);
        }

        $receipt = GoodsReceipt::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'received_by' => $request->user()->id,
        ]);

        $requisition->update(['status' => 'received']);

        return response()->json(['data' => $receipt, 'message' => 'Goods receipt recorded'], 201);
    }
}
