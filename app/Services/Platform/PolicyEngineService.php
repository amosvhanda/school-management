<?php

namespace App\Services\Platform;

use App\Models\PolicyRule;
use Illuminate\Support\Collection;

class PolicyEngineService
{
    public function listRules(int $schoolId): Collection
    {
        return PolicyRule::where('school_id', $schoolId)->orderBy('priority')->get();
    }

    public function upsertRule(int $schoolId, array $data, ?int $id = null): PolicyRule
    {
        $payload = [
            'school_id' => $schoolId,
            'code' => $data['code'],
            'name' => $data['name'],
            'module' => $data['module'],
            'trigger_event' => $data['trigger_event'],
            'conditions' => $data['conditions'] ?? [],
            'actions' => $data['actions'] ?? [],
            'priority' => $data['priority'] ?? 100,
            'is_active' => $data['is_active'] ?? true,
        ];

        if ($id) {
            $rule = PolicyRule::where('school_id', $schoolId)->findOrFail($id);
            $rule->update($payload);

            return $rule->fresh();
        }

        return PolicyRule::create($payload);
    }

    public function evaluate(int $schoolId, string $triggerEvent, array $context): array
    {
        $rules = PolicyRule::where('school_id', $schoolId)
            ->where('trigger_event', $triggerEvent)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        $results = [];

        foreach ($rules as $rule) {
            if ($this->conditionsMatch($rule->conditions ?? [], $context)) {
                $results[] = [
                    'rule_id' => $rule->id,
                    'rule_code' => $rule->code,
                    'actions' => $this->executeActions($rule->actions ?? [], $context),
                ];
            }
        }

        return $results;
    }

    protected function conditionsMatch(array $conditions, array $context): bool
    {
        if ($conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $expected = $condition['value'] ?? null;
            $actual = data_get($context, $field);

            $match = match ($operator) {
                'equals' => $actual == $expected,
                'not_equals' => $actual != $expected,
                'gt' => is_numeric($actual) && $actual > $expected,
                'gte' => is_numeric($actual) && $actual >= $expected,
                'lt' => is_numeric($actual) && $actual < $expected,
                'lte' => is_numeric($actual) && $actual <= $expected,
                'in' => in_array($actual, (array) $expected, true),
                'contains' => is_string($actual) && str_contains($actual, (string) $expected),
                default => false,
            };

            if (! $match) {
                return false;
            }
        }

        return true;
    }

    protected function executeActions(array $actions, array $context): array
    {
        $executed = [];

        foreach ($actions as $action) {
            $type = $action['type'] ?? 'log';
            $executed[] = match ($type) {
                'set_flag' => ['type' => 'set_flag', 'key' => $action['key'], 'value' => $action['value']],
                'notify_role' => ['type' => 'notify_role', 'role' => $action['role'], 'message' => $action['message'] ?? ''],
                'block' => ['type' => 'block', 'reason' => $action['reason'] ?? 'Policy blocked'],
                'require_approval' => ['type' => 'require_approval', 'workflow' => $action['workflow'] ?? null],
                default => ['type' => $type, 'payload' => $action],
            };
        }

        return $executed;
    }
}
