<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\User;
use App\Support\AuditLabels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function __construct(
        protected ?\App\Services\Platform\AuditIntegrityService $integrity = null,
    ) {}

    protected ?Request $request = null;

    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    public function enabled(): bool
    {
        return (bool) config('audit.enabled', true);
    }

    public function log(
        string $module,
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?array $metadata = null,
        ?User $user = null,
    ): ?AuditLog {
        if (! $this->enabled()) {
            return null;
        }

        $request = $this->request ?? request();
        $user ??= Auth::user();
        $context = $this->resolveRequestContext($request);

        $entry = AuditLog::create([
            'school_id' => $user?->school_id ?? ($auditable?->school_id ?? null),
            'user_id' => $user?->id,
            'module' => $module,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'old_values' => $this->sanitizeValues($oldValues),
            'new_values' => $this->sanitizeValues($newValues),
            'metadata' => $metadata,
            'ip_address' => $context['ip_address'],
            'user_agent' => $context['user_agent'],
            'device_type' => $context['device_type'],
            'platform' => $context['platform'],
            'location' => $context['location'],
            'request_method' => $request?->method(),
            'request_path' => $request?->path(),
            'created_at' => now(),
        ]);

        if ($this->integrity) {
            $this->integrity->sealLog($entry);
        }

        return $entry;
    }

    public function logModelEvent(Model $model, string $action): ?AuditLog
    {
        $module = method_exists($model, 'getAuditModule')
            ? $model->getAuditModule()
            : (config('audit.model_modules')[$model::class] ?? 'system');

        $modelName = AuditLabels::modelName($model::class);
        $identifier = AuditLabels::modelIdentifier($model);
        $description = AuditLabels::action($action)." {$modelName}: {$identifier}";

        return match ($action) {
            'created' => $this->log(
                module: $module,
                action: 'created',
                auditable: $model,
                newValues: $this->extractAuditableAttributes($model, $model->getAttributes()),
                description: $description,
            ),
            'updated' => $this->log(
                module: $module,
                action: 'updated',
                auditable: $model,
                oldValues: $this->extractAuditableAttributes($model, $model->getOriginal()),
                newValues: $this->extractAuditableAttributes($model, $model->getChanges()),
                description: $description,
            ),
            'deleted' => $this->log(
                module: $module,
                action: 'deleted',
                auditable: $model,
                oldValues: $this->extractAuditableAttributes($model, $model->getAttributes()),
                description: $description,
            ),
            default => null,
        };
    }

    public function logAuthEvent(
        string $event,
        ?User $user = null,
        ?string $email = null,
        ?string $failureReason = null,
        ?string $tokenName = null,
    ): ?LoginHistory {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $request = $this->request ?? request();
            $context = $this->resolveRequestContext($request);

            $entry = LoginHistory::create([
                'school_id' => $user?->school_id,
                'user_id' => $user?->id,
                'email' => $email ?? $user?->email,
                'event' => $event,
                'ip_address' => $context['ip_address'],
                'user_agent' => $context['user_agent'],
                'device_type' => $context['device_type'],
                'platform' => $context['platform'],
                'location' => $context['location'],
                'token_name' => $tokenName,
                'failure_reason' => $failureReason,
                'created_at' => now(),
            ]);

            if (in_array($event, ['login', 'logout', 'failed_login'], true)) {
                $this->log(
                    module: 'auth',
                    action: $event,
                    auditable: $user,
                    description: match ($event) {
                        'login' => ($user?->name ?? $email ?? 'User').' signed in',
                        'logout' => ($user?->name ?? $email ?? 'User').' signed out',
                        default => 'Failed sign-in attempt for '.($email ?? 'unknown email'),
                    },
                    metadata: array_filter([
                        'email' => $email ?? $user?->email,
                        'failure_reason' => $failureReason,
                        'token_name' => $tokenName,
                    ]),
                    user: $user,
                );
            }

            return $entry;
        } catch (\Throwable $e) {
            // Never block authentication because audit/history storage failed.
            report($e);

            return null;
        }
    }

    public function logExport(string $reportType, string $format, ?array $filters = null): ?AuditLog
    {
        return $this->log(
            module: 'reports',
            action: 'export',
            description: "Exported {$reportType} report as {$format}",
            metadata: [
                'report_type' => $reportType,
                'format' => $format,
                'filters' => $filters,
            ],
        );
    }

    /**
     * @return array{ip_address: ?string, user_agent: ?string, device_type: ?string, platform: ?string, location: ?string}
     */
    protected function resolveRequestContext(?Request $request): array
    {
        $userAgent = $request?->userAgent();
        $device = $this->parseUserAgent($userAgent);

        return [
            'ip_address' => $request?->ip(),
            'user_agent' => $userAgent,
            'device_type' => $device['device_type'],
            'platform' => $device['platform'],
            'location' => $request?->header('X-Geo-Location') ?? $request?->header('CF-IPCountry'),
        ];
    }

    /**
     * @return array{device_type: string, platform: string}
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        $ua = strtolower($userAgent ?? '');

        $deviceType = match (true) {
            str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') => 'mobile',
            str_contains($ua, 'tablet') || str_contains($ua, 'ipad') => 'tablet',
            $ua === '' => 'unknown',
            default => 'desktop',
        };

        $platform = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Unknown',
        };

        return ['device_type' => $deviceType, 'platform' => $platform];
    }

    protected function sanitizeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return Arr::except($values, config('audit.sensitive_attributes', []));
    }

    protected function extractAuditableAttributes(Model $model, array $attributes): array
    {
        $changedKeys = array_keys($attributes);
        $hidden = array_merge(
            config('audit.sensitive_attributes', []),
            $model->getHidden(),
        );

        return Arr::except(
            Arr::only($attributes, $changedKeys),
            $hidden,
        );
    }
}
