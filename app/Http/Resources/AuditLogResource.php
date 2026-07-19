<?php

namespace App\Http\Resources;

use App\Support\AuditLabels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AuditLog */
class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->whenLoaded('user');

        $actorName = $user?->name
            ?? ($user ? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) : null)
            ?: 'System';

        $targetName = $this->resolveTargetName();

        return [
            'id' => $this->id,
            'module' => $this->module,
            'module_label' => AuditLabels::module($this->module),
            'action' => $this->action,
            'action_label' => AuditLabels::action($this->action),
            'description' => $this->description,
            'summary' => AuditLabels::buildSummary(
                $this->module,
                $this->action,
                $this->description,
                $this->auditable_type,
                $this->auditable_id,
                $actorName !== 'System' ? $actorName : null,
                $targetName,
            ),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => is_object($user->role) ? $user->role->value : $user->role,
            ] : null,
            'actor' => [
                'id' => $user?->id,
                'name' => $actorName,
                'email' => $user?->email,
                'role' => $user ? (is_object($user->role) ? $user->role->value : $user->role) : null,
            ],
            'target' => [
                'type' => $this->auditable_type ? class_basename($this->auditable_type) : null,
                'type_label' => AuditLabels::modelName($this->auditable_type),
                'id' => $this->auditable_id,
                'name' => $targetName,
                'label' => $targetName,
            ],
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'changes' => $this->formatChanges(),
            'metadata' => $this->metadata,
            'context' => [
                'ip_address' => $this->ip_address,
                'device_type' => $this->device_type,
                'platform' => $this->platform,
                'location' => $this->location,
                'request_method' => $this->request_method,
                'request_path' => $this->request_path,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected function resolveTargetName(): ?string
    {
        if (! $this->auditable_type || ! $this->auditable_id) {
            return null;
        }

        try {
            /** @var class-string<Model>|null $type */
            $type = $this->auditable_type;
            if (! is_string($type) || ! class_exists($type)) {
                return null;
            }

            $model = $type::query()->find($this->auditable_id);
            if (! $model instanceof Model) {
                return null;
            }

            return AuditLabels::modelIdentifier($model);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{field: string, label: string, from: mixed, to: mixed}>
     */
    protected function formatChanges(): array
    {
        $old = is_array($this->old_values) ? $this->old_values : [];
        $new = is_array($this->new_values) ? $this->new_values : [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $hidden = AuditLabels::hiddenChangeFields();
        $changes = [];

        foreach ($keys as $key) {
            if (in_array($key, $hidden, true)) {
                continue;
            }

            $from = $old[$key] ?? null;
            $to = $new[$key] ?? null;
            if ($from === $to) {
                continue;
            }

            $changes[] = [
                'field' => $key,
                'label' => AuditLabels::fieldLabel($key),
                'from' => AuditLabels::displayValue($key, $from),
                'to' => AuditLabels::displayValue($key, $to),
            ];
        }

        return $changes;
    }
}
