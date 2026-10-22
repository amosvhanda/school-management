<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records successful mutating API calls in the audit trail so activity is logged
 * even when the underlying model does not use the Auditable trait.
 */
class LogApiActivity
{
    private const MUTATING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request, $response)) {
            $this->writeLog($request, $response);
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (! $this->audit->enabled()) {
            return false;
        }

        if (! in_array($request->method(), self::MUTATING, true)) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if (! $request->user()) {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');

        foreach (config('audit.http_exclude_paths', []) as $excluded) {
            if (Str::is($excluded, $path)) {
                return false;
            }
        }

        return true;
    }

    private function writeLog(Request $request, Response $response): void
    {
        try {
            $module = $this->resolveModule($request);
            $action = 'http_'.strtolower($request->method());
            $path = '/'.ltrim($request->path(), '/');

            $this->audit->log(
                module: $module,
                action: $action,
                description: strtoupper($request->method())." {$path}",
                metadata: [
                    'status' => $response->getStatusCode(),
                    'route' => $request->route()?->getName(),
                    'input' => $this->safeInput($request),
                ],
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function resolveModule(Request $request): string
    {
        $segments = array_values(array_filter(explode('/', $request->path())));
        // api / v1 / {resource}
        $resource = $segments[2] ?? ($segments[1] ?? 'system');
        $map = config('audit.http_modules', []);

        return $map[$resource] ?? Str::singular(str_replace('-', '_', $resource));
    }

    /**
     * @return array<string, mixed>
     */
    private function safeInput(Request $request): array
    {
        $sensitive = config('audit.sensitive_attributes', []);
        $payload = Arr::except($request->all(), $sensitive);

        // Drop bulky/binary fields and keep the trail readable.
        unset($payload['password'], $payload['password_confirmation'], $payload['file'], $payload['attachment'], $payload['photo'], $payload['content']);

        return $this->truncate($payload, 0);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function truncate(array $data, int $depth): array
    {
        if ($depth >= 3) {
            return ['_truncated' => true];
        }

        $out = [];
        $count = 0;
        foreach ($data as $key => $value) {
            if ($count >= 40) {
                $out['_truncated'] = true;
                break;
            }
            if (is_array($value)) {
                $out[$key] = $this->truncate($value, $depth + 1);
            } elseif (is_string($value) && strlen($value) > 200) {
                $out[$key] = substr($value, 0, 200).'…';
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            } else {
                $out[$key] = get_debug_type($value);
            }
            $count++;
        }

        return $out;
    }
}
