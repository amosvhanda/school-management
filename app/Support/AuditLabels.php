<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLabels
{
    /** @var array<string, string> */
    protected static array $modules = [
        'auth' => 'Authentication',
        'student' => 'Students',
        'administration' => 'Administration',
        'finance' => 'Finance',
        'examination' => 'Examinations',
        'discipline' => 'Discipline',
        'enrollment' => 'Enrollment',
        'attendance' => 'Attendance',
        'guardian' => 'Guardians',
        'hr' => 'Human resources',
        'reports' => 'Reports',
        'system' => 'System',
    ];

    /** @var array<string, string> */
    protected static array $actions = [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'login' => 'Signed in',
        'logout' => 'Signed out',
        'failed_login' => 'Failed sign-in',
        'export' => 'Exported',
        'payment_reversed' => 'Reversed payment',
        'approved' => 'Approved',
        'published' => 'Published',
        'recorded' => 'Recorded',
    ];

    public static function module(?string $module): string
    {
        if (! $module) {
            return 'System';
        }

        return self::$modules[$module] ?? Str::headline(str_replace('_', ' ', $module));
    }

    public static function action(?string $action): string
    {
        if (! $action) {
            return 'Action';
        }

        return self::$actions[$action] ?? Str::headline(str_replace('_', ' ', $action));
    }

    public static function modelName(?string $type): string
    {
        if (! $type) {
            return 'Record';
        }

        $base = class_basename($type);

        return Str::headline(preg_replace('/(?<!^)[A-Z]/', ' $0', $base) ?: $base);
    }

    public static function modelIdentifier(Model $model): string
    {
        foreach (['full_name', 'name', 'title', 'email', 'student_number', 'code'] as $field) {
            $value = $model->getAttribute($field);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '#'.$model->getKey();
    }

    public static function buildSummary(
        ?string $module,
        ?string $action,
        ?string $description,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?string $actorName = null,
    ): string {
        if ($description && ! str_contains($description, '#')) {
            return $description;
        }

        $actor = $actorName ?: 'Someone';
        $verb = strtolower(self::action($action));
        $area = self::module($module);

        if ($auditableType && $auditableId) {
            $target = self::modelName($auditableType).' #'.$auditableId;

            return "{$actor} {$verb} {$target} in {$area}";
        }

        return "{$actor} {$verb} in {$area}";
    }
}
