<?php

namespace App\Services;

use App\Models\InventorySale;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashFlowService
{
    /**
     * Build a cash-only money-in / money-out report that always balances:
     * net_cash = money_in.total − money_out.total
     *
     * @return array<string, mixed>
     */
    public function report(?int $schoolId, string $currency, Carbon $from, Carbon $to, string $period = 'monthly'): array
    {
        $currency = strtoupper($currency);
        $rangeEnd = $to->copy()->endOfDay();

        $feeCollections = (float) Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $feeReversals = (float) Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->where('status', 'reversed')
            ->whereBetween('updated_at', [$from, $rangeEnd])
            ->sum('amount');

        $netFeeCollections = round($feeCollections - $feeReversals, 2);

        $inventoryCash = (float) InventorySale::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->whereIn('payment_method', ['cash', 'upfront'])
            ->whereBetween('created_at', [$from, $rangeEnd])
            ->sum('total_amount');

        $ledgerFeeIn = (float) Transaction::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('currency', $currency)
            ->where('type', 'payment')
            ->where('category', 'student')
            ->whereBetween('created_at', [$from, $rangeEnd])
            ->sum('credit');

        $ledgerFeeReversals = (float) Transaction::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('currency', $currency)
            ->where('type', 'reversal')
            ->where('category', 'student')
            ->whereBetween('created_at', [$from, $rangeEnd])
            ->sum('debit');

        $ledgerInventoryIn = (float) Transaction::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('currency', $currency)
            ->where('type', 'income')
            ->where('category', 'inventory')
            ->whereBetween('created_at', [$from, $rangeEnd])
            ->sum('credit');

        $ledgerMoneyIn = round($ledgerFeeIn - $ledgerFeeReversals + $ledgerInventoryIn, 2);

        $payrollOut = (float) Transaction::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('currency', $currency)
            ->where('type', 'expense')
            ->where('category', 'payroll')
            ->whereBetween('created_at', [$from, $rangeEnd])
            ->sum('debit');

        $otherExpenseOut = (float) Transaction::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('currency', $currency)
            ->where('type', 'expense')
            ->where('category', '!=', 'payroll')
            ->whereBetween('created_at', [$from, $rangeEnd])
            ->sum('debit');

        $moneyInTotal = round($netFeeCollections + $inventoryCash, 2);
        $moneyOutTotal = round($payrollOut + $otherExpenseOut, 2);
        $netCash = round($moneyInTotal - $moneyOutTotal, 2);

        $inVariance = round($moneyInTotal - $ledgerMoneyIn, 2);
        $outVariance = round($moneyOutTotal - ($payrollOut + $otherExpenseOut), 2);
        // Out is taken from the ledger today, so out variance should be 0.
        // In variance flags historical inventory cash sales not yet posted to the ledger.
        $isBalanced = abs($inVariance) < 0.01 && abs($outVariance) < 0.01;

        $feesInvoiced = (float) Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->whereBetween('created_at', [$from, $rangeEnd])
            ->sum('amount');

        $feesOutstanding = (float) Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->where('balance', '>', 0)
            ->sum('balance');

        $payrollPending = Payroll::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->whereIn('status', ['pending', 'partial'])
            ->get()
            ->sum(fn (Payroll $row) => max(0, (float) $row->net_salary - (float) $row->amount_paid));

        return [
            'period' => $period,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'currency' => $currency,
            'equation' => 'Money in − Money out = Net cash',
            'money_in' => [
                'total' => $moneyInTotal,
                'fee_collections' => round($netFeeCollections, 2),
                'fee_collections_gross' => round($feeCollections, 2),
                'fee_reversals' => round($feeReversals, 2),
                'inventory_cash' => round($inventoryCash, 2),
                'by_method' => $this->moneyInByMethod($schoolId, $currency, $from, $rangeEnd),
                'by_source' => [
                    ['source' => 'fee_collections', 'label' => 'Student fee collections', 'total' => round($netFeeCollections, 2)],
                    ['source' => 'inventory_cash', 'label' => 'Inventory / store till', 'total' => round($inventoryCash, 2)],
                ],
            ],
            'money_out' => [
                'total' => $moneyOutTotal,
                'payroll' => round($payrollOut, 2),
                'other_expenses' => round($otherExpenseOut, 2),
                'by_method' => $this->moneyOutByMethod($schoolId, $currency, $from, $rangeEnd),
                'by_source' => [
                    ['source' => 'payroll', 'label' => 'Staff payroll', 'total' => round($payrollOut, 2)],
                    ['source' => 'other_expenses', 'label' => 'Other expenses', 'total' => round($otherExpenseOut, 2)],
                ],
            ],
            'net_cash' => $netCash,
            'books' => [
                'fees_invoiced' => round($feesInvoiced, 2),
                'fees_outstanding' => round($feesOutstanding, 2),
                'payroll_pending' => round((float) $payrollPending, 2),
                'note' => 'Books track what is owed; cash tracks what has moved through the till and bank.',
            ],
            'balance_check' => [
                'ledger_money_in' => $ledgerMoneyIn,
                'ledger_money_out' => round($payrollOut + $otherExpenseOut, 2),
                'source_money_in' => $moneyInTotal,
                'source_money_out' => $moneyOutTotal,
                'in_variance' => $inVariance,
                'out_variance' => $outVariance,
                'is_balanced' => $isBalanced,
                'message' => $isBalanced
                    ? 'Cash sources match the ledger for this period.'
                    : 'Cash sources and ledger differ. Historical inventory till sales may pre-date ledger posting.',
            ],
        ];
    }

    /**
     * @return list<array{method: string, total: float, count: int}>
     */
    protected function moneyInByMethod(?int $schoolId, string $currency, Carbon $from, Carbon $to): array
    {
        $payments = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get();

        $inventory = InventorySale::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->whereIn('payment_method', ['cash', 'upfront'])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('payment_method as method, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->get();

        return $this->mergeMethodRows($payments->concat($inventory));
    }

    /**
     * @return list<array{method: string, total: float, count: int}>
     */
    protected function moneyOutByMethod(?int $schoolId, string $currency, Carbon $from, Carbon $to): array
    {
        $rows = Transaction::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('currency', $currency)
            ->where('type', 'expense')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COALESCE(payment_method, 'unspecified') as method, COUNT(*) as count, SUM(debit) as total")
            ->groupBy('method')
            ->get();

        return $this->mergeMethodRows($rows);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array{method: string, total: float, count: int}>
     */
    protected function mergeMethodRows(Collection $rows): array
    {
        $merged = [];

        foreach ($rows as $row) {
            $method = strtolower((string) ($row->method ?? 'unspecified'));
            if (! isset($merged[$method])) {
                $merged[$method] = ['method' => $method, 'total' => 0.0, 'count' => 0];
            }
            $merged[$method]['total'] = round($merged[$method]['total'] + (float) $row->total, 2);
            $merged[$method]['count'] += (int) $row->count;
        }

        return array_values($merged);
    }
}
