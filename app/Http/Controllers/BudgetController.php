<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return Budget::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageFinance'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['finance.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'Budget';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'fiscal_year' => ['required', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'spent_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'closed'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'fiscal_year' => ['sometimes', 'required', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'allocated_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'spent_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'closed'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function applyIndexFilters(Builder $query, Request $request): Builder
    {
        $query = parent::applyIndexFilters($query, $request);

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->string('fiscal_year')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('department')) {
            $query->where('department', 'like', '%'.$request->string('department')->toString().'%');
        }

        return $query->orderByDesc('fiscal_year')->orderBy('name');
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);
        $budget = $this->schoolQuery($request)->findOrFail($id);

        if (! in_array($budget->status, ['draft', 'submitted'], true)) {
            return response()->json(['message' => 'Only draft budgets can be submitted.'], 422);
        }

        $budget->update(['status' => 'submitted']);

        return response()->json(['data' => $budget->fresh(), 'message' => 'Budget submitted for approval']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);
        $budget = $this->schoolQuery($request)->findOrFail($id);

        if ($budget->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted budgets can be approved.'], 422);
        }

        $budget->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json(['data' => $budget->fresh(), 'message' => 'Budget approved']);
    }

    public function recordSpend(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);
        $budget = $this->schoolQuery($request)->findOrFail($id);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($budget->status !== 'approved') {
            return response()->json(['message' => 'Spend can only be recorded against approved budgets.'], 422);
        }

        $nextSpent = (float) $budget->spent_amount + (float) $data['amount'];
        if ($nextSpent > (float) $budget->allocated_amount + 0.001) {
            return response()->json(['message' => 'Spend would exceed the allocated budget.'], 422);
        }

        $notes = trim((string) ($budget->notes ?? ''));
        if (! empty($data['note'])) {
            $line = now()->toDateString().': '.$data['note'].' ('.$data['amount'].')';
            $notes = $notes === '' ? $line : $notes."\n".$line;
        }

        $budget->update([
            'spent_amount' => $nextSpent,
            'notes' => $notes !== '' ? $notes : $budget->notes,
        ]);

        return response()->json(['data' => $budget->fresh(), 'message' => 'Spend recorded']);
    }
}
