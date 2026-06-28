<?php

namespace App\Services\Enterprise;

use App\Models\BankStatementLine;
use App\Models\ChartOfAccount;
use App\Models\ExchangeRate;
use App\Models\InstalmentPlan;
use App\Models\InstalmentScheduleItem;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceEnterpriseService
{
    public function seedDefaultAccounts(int $schoolId): void
    {
        $defaults = [
            ['code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '3000', 'name' => 'Equity', 'account_type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '4000', 'name' => 'Tuition Revenue', 'account_type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => '5000', 'name' => 'Operating Expenses', 'account_type' => 'expense', 'normal_balance' => 'debit'],
        ];

        foreach ($defaults as $account) {
            ChartOfAccount::firstOrCreate(
                ['school_id' => $schoolId, 'code' => $account['code']],
                array_merge($account, ['school_id' => $schoolId]),
            );
        }
    }

    public function postJournal(int $schoolId, array $data, ?int $userId = null): JournalEntry
    {
        $lines = $data['lines'];
        $totalDebit = collect($lines)->sum('debit');
        $totalCredit = collect($lines)->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            abort(422, 'Journal entry must balance (debits must equal credits).');
        }

        return DB::transaction(function () use ($schoolId, $data, $lines, $userId) {
            $entry = JournalEntry::create([
                'school_id' => $schoolId,
                'reference' => $data['reference'] ?? 'JE-'.strtoupper(Str::random(8)),
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'description' => $data['description'],
                'status' => 'posted',
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'currency' => $line['currency'] ?? $entry->currency,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    public function convertAmount(float $amount, string $from, string $to, int $schoolId): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = ExchangeRate::where('school_id', $schoolId)
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->orderByDesc('effective_date')
            ->first();

        return $rate ? round($amount * (float) $rate->rate, 2) : $amount;
    }

    public function createInstalmentPlan(int $schoolId, array $data): InstalmentPlan
    {
        return DB::transaction(function () use ($schoolId, $data) {
            $plan = InstalmentPlan::create([
                'school_id' => $schoolId,
                'student_id' => $data['student_id'],
                'invoice_id' => $data['invoice_id'] ?? null,
                'total_amount' => $data['total_amount'],
                'currency' => $data['currency'] ?? 'USD',
                'status' => 'active',
            ]);

            foreach ($data['schedule'] as $i => $item) {
                InstalmentScheduleItem::create([
                    'plan_id' => $plan->id,
                    'installment_number' => $i + 1,
                    'due_date' => $item['due_date'],
                    'amount' => $item['amount'],
                ]);
            }

            return $plan->load('items');
        });
    }

    public function reconcileBankLine(int $lineId, int $paymentId): BankStatementLine
    {
        $line = BankStatementLine::findOrFail($lineId);
        $payment = Payment::findOrFail($paymentId);

        $line->update([
            'matched_payment_id' => $payment->id,
            'match_status' => 'matched',
        ]);

        return $line->fresh();
    }

    public function profitAndLoss(int $schoolId, string $from, string $to): array
    {
        $revenue = $this->accountTypeTotal($schoolId, 'revenue', $from, $to);
        $expenses = $this->accountTypeTotal($schoolId, 'expense', $from, $to);

        return [
            'period' => ['from' => $from, 'to' => $to],
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => round($revenue - $expenses, 2),
        ];
    }

    public function balanceSheet(int $schoolId, string $asOf): array
    {
        return [
            'as_of' => $asOf,
            'assets' => $this->accountTypeTotal($schoolId, 'asset', null, $asOf),
            'liabilities' => $this->accountTypeTotal($schoolId, 'liability', null, $asOf),
            'equity' => $this->accountTypeTotal($schoolId, 'equity', null, $asOf),
        ];
    }

    public function cashflowForecast(int $schoolId, int $months = 3): array
    {
        $expectedCollections = InstalmentScheduleItem::query()
            ->whereHas('plan', fn ($q) => $q->where('school_id', $schoolId)->where('status', 'active'))
            ->where('status', 'pending')
            ->where('due_date', '<=', now()->addMonths($months))
            ->sum('amount');

        return [
            'horizon_months' => $months,
            'projected_collections' => round((float) $expectedCollections, 2),
            'projected_expenses' => 0,
            'net_cashflow' => round((float) $expectedCollections, 2),
        ];
    }

    protected function accountTypeTotal(int $schoolId, string $type, ?string $from, ?string $to): float
    {
        $accountIds = ChartOfAccount::where('school_id', $schoolId)->where('account_type', $type)->pluck('id');

        $query = JournalLine::whereIn('account_id', $accountIds)
            ->whereHas('entry', function ($q) use ($schoolId, $from, $to) {
                $q->where('school_id', $schoolId)->where('status', 'posted');
                if ($from) {
                    $q->where('entry_date', '>=', $from);
                }
                if ($to) {
                    $q->where('entry_date', '<=', $to);
                }
            });

        $debits = (float) (clone $query)->sum('debit');
        $credits = (float) (clone $query)->sum('credit');

        return in_array($type, ['asset', 'expense'], true) ? $debits - $credits : $credits - $debits;
    }
}
