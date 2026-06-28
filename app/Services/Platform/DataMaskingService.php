<?php

namespace App\Services\Platform;

use App\Enums\UserRole;
use App\Models\DataMaskingRule;
use App\Models\User;

class DataMaskingService
{
    public function maskPayload(User $user, string $module, array $data): array
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        if ($role === 'school_admin') {
            $role = 'admin';
        }

        if (in_array($role, ['admin', 'super_admin'], true)) {
            return $data;
        }

        $rules = DataMaskingRule::where('school_id', $user->school_id)
            ->where('module', $module)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if (in_array($role, $rule->visible_roles ?? [], true)) {
                continue;
            }

            if (array_key_exists($rule->field, $data)) {
                $data[$rule->field] = $rule->mask_pattern;
            }
        }

        return $data;
    }
}
