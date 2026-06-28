<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventorySale;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $items = InventoryItem::where('school_id', $schoolId)
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'stock_quantity' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'billing_mode' => 'nullable|in:direct_sale,mandatory_fee,both',
            'description' => 'nullable|string',
        ]);

        $item = InventoryItem::create([
            'school_id' => $schoolId,
            ...$data,
            'currency' => $data['currency'] ?? 'USD',
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'is_active' => true,
        ]);

        return response()->json(['data' => $item, 'message' => 'Inventory item created'], 201);
    }

    public function update(Request $request, int $id)
    {
        $item = InventoryItem::where('school_id', $request->user()->school_id)->findOrFail($id);
        $item->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'unit_price' => 'sometimes|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
            'billing_mode' => 'sometimes|in:direct_sale,mandatory_fee,both',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json(['data' => $item]);
    }

    public function restock(Request $request, int $id)
    {
        $item = InventoryItem::where('school_id', $request->user()->school_id)->findOrFail($id);
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $movement = $this->inventoryService->restock($item, $data['quantity'], $request->user()->id, $data['notes'] ?? null);

        return response()->json(['data' => $movement, 'item' => $item->fresh()]);
    }

    public function sales(Request $request)
    {
        $sales = InventorySale::where('school_id', $request->user()->school_id)
            ->with(['student', 'items.item'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $sales]);
    }

    public function createSale(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,student_account,upfront',
            'student_id' => 'nullable|exists:students,id',
            'notes' => 'nullable|string',
        ]);

        $sale = $this->inventoryService->createSale(
            schoolId: $request->user()->school_id,
            items: $data['items'],
            paymentMethod: $data['payment_method'],
            studentId: $data['student_id'] ?? null,
            soldBy: $request->user()->id,
            notes: $data['notes'] ?? null,
        );

        return response()->json(['data' => $sale, 'message' => 'Sale completed and stock updated'], 201);
    }
}
