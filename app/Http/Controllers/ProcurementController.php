<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProcurementController extends Controller
{
    public function __construct(private WorkflowService $workflows) {}

    public function requisitions(Request $request)
    {
        $items = PurchaseRequisition::query()
            ->where('school_id', $request->user()->school_id)
            ->with(['requester:id,name', 'items', 'workflowInstance'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function storeRequisition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
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
                'title' => $request->title,
                'description' => $request->description,
                'estimated_cost' => $estimated,
                'status' => $request->boolean('submit') ? 'pending_approval' : 'draft',
            ]);

            foreach ($request->items as $item) {
                $req->items()->create($item);
            }

            if ($request->boolean('submit')) {
                $instance = $this->workflows->start('purchase_request', $req, $user, ['title' => $req->title]);
                $req->update(['workflow_instance_id' => $instance->id]);
            }

            return $req->load('items', 'workflowInstance');
        });

        return response()->json(['data' => $requisition], 201);
    }

    public function vendors(Request $request)
    {
        return response()->json(['data' => Vendor::where('school_id', $request->user()->school_id)->orderBy('name')->get()]);
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

        $receipt = GoodsReceipt::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'received_by' => $request->user()->id,
        ]);

        $requisition->update(['status' => 'received']);

        return response()->json(['data' => $receipt], 201);
    }
}
