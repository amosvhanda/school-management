<?php

namespace App\Services\Tenancy;

use App\Models\School;
use Illuminate\Support\Facades\Cache;

class TenantCache
{
    public function __construct(private readonly ?int $schoolId) {}

    public static function for(?int $schoolId): self
    {
        return new self($schoolId);
    }

    public static function current(): self
    {
        $school = app()->bound('currentSchool') ? app('currentSchool') : null;

        return new self($school instanceof School ? (int) $school->id : null);
    }

    public function key(string $key): string
    {
        $prefix = $this->schoolId !== null ? "tenant:{$this->schoolId}" : 'tenant:central';

        return "{$prefix}:{$key}";
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($this->key($key), $default);
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds = null): bool
    {
        if ($ttlSeconds === null) {
            return Cache::forever($this->key($key), $value);
        }

        return Cache::put($this->key($key), $value, $ttlSeconds);
    }

    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        return Cache::remember($this->key($key), $ttlSeconds, $callback);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($this->key($key));
    }
}
