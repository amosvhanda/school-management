<?php

namespace App\Support;

use App\Models\ClassModel;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
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
        'workflow' => 'Workflows',
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
        'rejected' => 'Rejected',
        'recorded' => 'Recorded',
    ];

    /** @var array<string, class-string<Model>> */
    protected static array $relationFields = [
        'teacher_id' => Teacher::class,
        'student_id' => Student::class,
        'user_id' => User::class,
        'requested_by' => User::class,
        'reviewed_by' => User::class,
        'approved_by' => User::class,
        'created_by' => User::class,
        'host_user_id' => User::class,
        'custodian_user_id' => User::class,
        'guardian_id' => Guardian::class,
        'class_id' => ClassModel::class,
        'subject_id' => Subject::class,
    ];

    /** Fields that are noise in change diffs. */
    public static function hiddenChangeFields(): array
    {
        return [
            'id',
            'school_id',
            'created_at',
            'updated_at',
            'deleted_at',
            'password',
            'remember_token',
        ];
    }

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
        if (method_exists($model, 'getAuditIdentifier')) {
            $custom = $model->getAuditIdentifier();
            if (is_string($custom) && trim($custom) !== '') {
                return trim($custom);
            }
        }

        foreach (['full_name', 'name', 'title', 'email', 'student_number', 'code', 'reference'] as $field) {
            $value = $model->getAttribute($field);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        // Prefer first+last when present (teachers/users).
        $first = $model->getAttribute('first_name');
        $last = $model->getAttribute('last_name');
        if (is_string($first) || is_string($last)) {
            $combined = trim(($first ?? '').' '.($last ?? ''));
            if ($combined !== '') {
                return $combined;
            }
        }

        return self::modelName($model::class);
    }

    public static function fieldLabel(string $key): string
    {
        $labels = [
            'teacher_id' => 'Teacher',
            'student_id' => 'Student',
            'user_id' => 'User',
            'requested_by' => 'Requested by',
            'reviewed_by' => 'Reviewed by',
            'approved_by' => 'Approved by',
            'class_id' => 'Class',
            'subject_id' => 'Subject',
            'guardian_id' => 'Guardian',
            'start_date' => 'Start date',
            'end_date' => 'End date',
            'reviewed_at' => 'Reviewed at',
            'review_notes' => 'Review notes',
        ];

        return $labels[$key] ?? Str::headline(str_replace('_', ' ', $key));
    }

    /**
     * Resolve a stored attribute value into a human-readable label when possible.
     */
    public static function displayValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (in_array($field, ['start_date', 'end_date', 'reviewed_at', 'joining_date', 'date'], true) && is_string($value)) {
            try {
                $parsed = \Illuminate\Support\Carbon::parse($value);
                // Date-only fields should not show midnight noise.
                if (in_array($field, ['start_date', 'end_date', 'joining_date', 'date'], true)
                    || ($parsed->hour === 0 && $parsed->minute === 0 && $parsed->second === 0)) {
                    return $parsed->format('j M Y');
                }

                return $parsed->format('j M Y H:i');
            } catch (\Throwable) {
                return $value;
            }
        }

        if (isset(self::$relationFields[$field]) && (is_int($value) || (is_string($value) && ctype_digit($value)))) {
            $modelClass = self::$relationFields[$field];
            $related = $modelClass::query()->find((int) $value);
            if ($related) {
                return self::modelIdentifier($related);
            }
        }

        if ($field === 'type' && is_string($value)) {
            return Str::headline(str_replace('_', ' ', $value));
        }

        if ($field === 'status' && is_string($value)) {
            return Str::headline(str_replace('_', ' ', $value));
        }

        return $value;
    }

    public static function buildSummary(
        ?string $module,
        ?string $action,
        ?string $description,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?string $actorName = null,
        ?string $targetName = null,
    ): string {
        // Prefer a clean stored description that is not ID-heavy.
        if ($description && ! preg_match('/#\d+\b/', $description)) {
            return $description;
        }

        $actor = $actorName ?: 'Someone';
        $verb = strtolower(self::action($action));
        $area = self::module($module);

        if ($targetName) {
            $type = self::modelName($auditableType);

            return "{$actor} {$verb} {$type} “{$targetName}” in {$area}";
        }

        if ($auditableType) {
            return "{$actor} {$verb} a ".strtolower(self::modelName($auditableType))." in {$area}";
        }

        return "{$actor} {$verb} in {$area}";
    }
}
