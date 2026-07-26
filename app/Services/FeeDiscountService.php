<?php

namespace App\Services;

use App\Models\FeeDiscount;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Support\Collection;

class FeeDiscountService
{
    /**
     * Find active discounts that apply to this student + optional fee category.
     *
     * @return Collection<int, FeeDiscount>
     */
    public function matchingDiscounts(Student $student, ?int $feeCategoryId = null, ?\DateTimeInterface $onDate = null): Collection
    {
        $on = $onDate ? \Carbon\Carbon::parse($onDate)->toDateString() : now()->toDateString();

        return FeeDiscount::query()
            ->where('school_id', $student->school_id)
            ->where('is_active', true)
            ->where(function ($q) use ($on) {
                $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $on);
            })
            ->where(function ($q) use ($on) {
                $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $on);
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('student_category_id');
                if ($student->student_category_id) {
                    $q->orWhere('student_category_id', $student->student_category_id);
                }
            })
            ->where(function ($q) use ($feeCategoryId) {
                $q->whereNull('fee_category_id');
                if ($feeCategoryId) {
                    $q->orWhere('fee_category_id', $feeCategoryId);
                }
            })
            ->orderByDesc('value')
            ->get();
    }

    /**
     * Apply the best matching discount (highest absolute reduction).
     *
     * @return array{amount: float, discount: ?FeeDiscount, discount_amount: float, original_amount: float}
     */
    public function applyBestDiscount(Student $student, float $amount, ?int $feeCategoryId = null, ?\DateTimeInterface $onDate = null): array
    {
        $original = round(max(0, $amount), 2);
        $best = null;
        $bestDiscountAmount = 0.0;

        foreach ($this->matchingDiscounts($student, $feeCategoryId, $onDate) as $discount) {
            $reduction = $this->calculateReduction($original, $discount);
            if ($reduction > $bestDiscountAmount) {
                $bestDiscountAmount = $reduction;
                $best = $discount;
            }
        }

        $final = round(max(0, $original - $bestDiscountAmount), 2);

        return [
            'amount' => $final,
            'discount' => $best,
            'discount_amount' => round($bestDiscountAmount, 2),
            'original_amount' => $original,
        ];
    }

    public function calculateReduction(float $amount, FeeDiscount $discount): float
    {
        if ($discount->discount_type === 'percent') {
            return round($amount * ((float) $discount->value / 100), 2);
        }

        return min($amount, round((float) $discount->value, 2));
    }

    public function feeCategoryIdFromStructure(?FeeStructure $structure): ?int
    {
        if (! $structure) {
            return null;
        }

        return $structure->fee_category_id ? (int) $structure->fee_category_id : null;
    }
}
