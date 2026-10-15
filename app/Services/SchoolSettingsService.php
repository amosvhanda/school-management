<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Services\Tenancy\TenantCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchoolSettingsService
{
    public function getAll(School $school, bool $publicOnly = false): array
    {
        $cacheKey = $publicOnly ? 'settings:public' : 'settings:all';

        return TenantCache::for($school->id)->remember($cacheKey, 300, function () use ($school, $publicOnly) {
            $query = SchoolSetting::withoutGlobalScopes()
                ->where('school_id', $school->id);

            if ($publicOnly) {
                $query->where('is_public', true);
            }

            $stored = $query->get()->groupBy('group');

            $result = [];
            foreach (config('school.definitions', []) as $group => $definitions) {
                if ($publicOnly) {
                    $definitions = array_filter(
                        $definitions,
                        static fn (array $definition) => (bool) ($definition['public'] ?? false),
                    );
                }

                $result[$group] = [];
                foreach ($definitions as $key => $definition) {
                    $storedSetting = $stored->get($group)?->firstWhere('key', $key);
                    $value = $storedSetting?->casted_value
                        ?? $definition['default']
                        ?? $school->{$key} ?? null;

                    if (($definition['type'] ?? null) === 'encrypted' && $value !== null && $value !== '') {
                        $value = '********';
                    }

                    $result[$group][$key] = $value;
                }
            }

            foreach ($stored as $group => $items) {
                if (! isset($result[$group])) {
                    $result[$group] = [];
                }
                foreach ($items as $item) {
                    if ($publicOnly && ! $item->is_public) {
                        continue;
                    }
                    if (! array_key_exists($item->key, $result[$group])) {
                        $result[$group][$item->key] = $item->casted_value;
                    }
                }
            }

            // Canonical fees currency lives on the school record.
            if (isset($result['regional'])) {
                $result['regional']['currency'] = $school->getDefaultCurrency();
            }

            return $result;
        });
    }

    public function get(School $school, string $group, string $key, mixed $default = null): mixed
    {
        $setting = SchoolSetting::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if ($setting) {
            return $setting->casted_value;
        }

        $definition = config("school.definitions.{$group}.{$key}");

        return $definition['default'] ?? $default;
    }

    public function set(School $school, string $group, string $key, mixed $value, ?string $type = null, bool $isPublic = false): SchoolSetting
    {
        $definition = config("school.definitions.{$group}.{$key}", []);
        $type ??= $definition['type'] ?? $this->inferType($value);
        $isPublic = $definition['public'] ?? $isPublic;

        if ($group === 'regional' && $key === 'currency') {
            app(SchoolConfigurationService::class)->setSchoolCurrency($school, (string) $value);

            $setting = SchoolSetting::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->where('group', 'regional')
                ->where('key', 'currency')
                ->firstOrFail();

            $this->forgetCache($school);

            return $setting;
        }

            // Keep existing encrypted password when blank or masked placeholder is submitted.
            if ($type === 'encrypted' && ($value === null || $value === '' || $value === '********')) {
                $existing = SchoolSetting::withoutGlobalScopes()
                    ->where('school_id', $school->id)
                    ->where('group', $group)
                    ->where('key', $key)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

        $setting = DB::transaction(function () use ($school, $group, $key, $value, $type, $isPublic) {
            $setting = SchoolSetting::withoutGlobalScopes()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'group' => $group,
                    'key' => $key,
                ],
                [
                    'type' => $type,
                    'is_public' => $isPublic,
                ]
            );

            $setting->setTypedValue($value);
            $setting->save();

            return $setting->fresh();
        });

        $this->forgetCache($school);

        return $setting;
    }

    public function bulkSet(School $school, array $settings): Collection
    {
        return collect($settings)->map(function (array $item) use ($school) {
            return $this->set(
                $school,
                $item['group'],
                $item['key'],
                $item['value'],
                $item['type'] ?? null,
                $item['is_public'] ?? false
            );
        });
    }

    public function seedDefaults(School $school): void
    {
        foreach (config('school.definitions', []) as $group => $definitions) {
            foreach ($definitions as $key => $definition) {
                $default = $definition['default'];
                if ($key === 'academic_year' && $default === null) {
                    $default = (string) now()->year;
                }
                if ($key === 'school_name' && $default === null) {
                    $default = $school->name;
                }
                if ($group === 'regional' && $key === 'currency') {
                    $default = $school->getDefaultCurrency();
                }
                if ($group === 'mail' && $key === 'from_name' && $default === null) {
                    $default = $school->name;
                }
                if ($group === 'mail' && $key === 'from_address' && $default === null) {
                    $default = $school->email;
                }

                $this->set(
                    $school,
                    $group,
                    $key,
                    $default,
                    $definition['type'],
                    $definition['public'] ?? false
                );
            }
        }

        $this->forgetCache($school);
    }

    public function forgetCache(School $school): void
    {
        $cache = TenantCache::for($school->id);
        $cache->forget('settings:all');
        $cache->forget('settings:public');
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
