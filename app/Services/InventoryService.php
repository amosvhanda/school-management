<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventorySale;
use App\Models\InventorySaleItem;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private FinancialLedgerService $ledgerService) {}

    /**
     * @param  array<int, array{item_id: int, quantity: int}>  $items
     */
    public function createSale(
        int $schoolId,
        array $items,
        string $paymentMethod,
        ?int $studentId,
        ?int $soldBy,
        ?string $notes = null,
    ): InventorySale {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => ['At least one item is required.']]);
        }

        return DB::transaction(function () use ($schoolId, $items, $paymentMethod, $studentId, $soldBy, $notes) {
            $saleNumber = 'SALE-'.date('Y').'-'.str_pad(
                (string) (InventorySale::where('school_id', $schoolId)->count() + 1),
                4,
                '0',
                STR_PAD_LEFT,
            );

            $total = 0;
            $currency = $this->ledgerService->schoolCurrency($schoolId);
            $lineRows = [];

            foreach ($items as $row) {
                $item = InventoryItem::where('school_id', $schoolId)->lockForUpdate()->findOrFail($row['item_id']);
                $qty = (int) $row['quantity'];

                if ($qty < 1) {
                    throw ValidationException::withMessages(['items' => ['Quantity must be at least 1.']]);
                }

                if ($item->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for {$item->name}. Available: {$item->stock_quantity}"],
                    ]);
                }

                $lineTotal = $qty * (float) $item->unit_price;
                $total += $lineTotal;
                $currency = $item->currency;
                $lineRows[] = compact('item', 'qty', 'lineTotal');
            }

            $invoice = null;
            $student = $studentId ? Student::where('school_id', $schoolId)->find($studentId) : null;

            if ($paymentMethod === 'student_account' && $student) {
                $invoice = $this->ledgerService->createInvoice(
                    student: $student,
                    amount: $total,
                    description: "Uniform/store sale {$saleNumber}",
                    createdBy: $soldBy,
                );
            }

            $sale = InventorySale::create([
                'school_id' => $schoolId,
                'sale_number' => $saleNumber,
                'student_id' => $student?->id,
                'total_amount' => $total,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'invoice_id' => $invoice?->id,
                'sold_by' => $soldBy,
                'notes' => $notes,
            ]);

            foreach ($lineRows as $line) {
                /** @var InventoryItem $item */
                $item = $line['item'];
                $qty = $line['qty'];

                InventorySaleItem::create([
                    'inventory_sale_id' => $sale->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $qty,
                    'unit_price' => $item->unit_price,
                    'line_total' => $line['lineTotal'],
                ]);

                $item->decrement('stock_quantity', $qty);

                InventoryMovement::create([
                    'school_id' => $schoolId,
                    'inventory_item_id' => $item->id,
                    'type' => 'sale',
                    'quantity_change' => -$qty,
                    'quantity_after' => $item->fresh()->stock_quantity,
                    'reference_type' => InventorySale::class,
                    'reference_id' => $sale->id,
                    'created_by' => $soldBy,
                ]);
            }

            return $sale->load('items.item');
        });
    }

    public function restock(InventoryItem $item, int $quantity, ?int $userId, ?string $notes = null): InventoryMovement
{
    return DB::transaction(function () use ($item, $quantity, $userId, $notes) {
        $item->increment('stock_quantity', $quantity);

        return InventoryMovement::create([
            'school_id' => $item->school_id,
            'inventory_item_id' => $item->id,
            'type' => 'restock',
            'quantity_change' => $quantity,
            'quantity_after' => $item->stock_quantity, // No need for fresh() inside the transaction
            'notes' => $notes,
            'created_by' => $userId,
        ]);
    });
}
}
