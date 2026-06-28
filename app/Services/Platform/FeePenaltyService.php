<?php

namespace App\Services\Platform;

use App\Models\FeePenalty;
use App\Models\FeePenaltyRule;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class FeePenaltyService
{
    public function applyPenalties(int $schoolId): int
    {
        $rules = FeePenaltyRule::where('school_id', $schoolId)->where('is_active', true)->get();
        if ($rules->isEmpty()) {
            return 0;
        }

        $applied = 0;

        $invoices = Invoice::where('school_id', $schoolId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now()->toDateString())
            ->get();

        foreach ($invoices as $invoice) {
            foreach ($rules as $rule) {
                $daysOverdue = now()->diffInDays($invoice->due_date);
                if ($daysOverdue <= $rule->grace_days) {
                    continue;
                }

                $exists = FeePenalty::where('invoice_id', $invoice->id)
                    ->where('rule_id', $rule->id)
                    ->whereDate('applied_on', now()->toDateString())
                    ->exists();

                if ($exists && $rule->frequency === 'once') {
                    continue;
                }

                if ($exists && $rule->frequency === 'daily') {
                    // allow daily
                } elseif ($exists) {
                    continue;
                }

                $amount = $this->calculatePenalty($invoice, $rule);

                DB::transaction(function () use ($schoolId, $rule, $invoice, $amount) {
                    FeePenalty::create([
                        'school_id' => $schoolId,
                        'rule_id' => $rule->id,
                        'invoice_id' => $invoice->id,
                        'student_id' => $invoice->student_id,
                        'amount' => $amount,
                        'applied_on' => now()->toDateString(),
                    ]);

                    $invoice->increment('amount', $amount);
                    $invoice->increment('balance', $amount);
                });

                $applied++;
            }
        }

        return $applied;
    }

    protected function calculatePenalty(Invoice $invoice, FeePenaltyRule $rule): float
    {
        return match ($rule->penalty_type) {
            'fixed' => (float) $rule->penalty_value,
            'percentage' => round((float) $invoice->balance * ((float) $rule->penalty_value / 100), 2),
            default => (float) $rule->penalty_value,
        };
    }
}
