<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchoolSettingsService
{
    public function getAll(School $school, bool $publicOnly = false): array
    {
        $query = SchoolSetting::withoutGlobalScopes()
            ->where('school_id', $school->id);

        if ($publicOnly) {
            $query->where('is_public', true);
        }

        $stored = $query->get()->groupBy('group');

        $result = [];
        foreach (config('school.definitions', []) as $group => $definitions) {
            $result[$group] = [];
            foreach ($definitions as $key => $definition) {
                $storedSetting = $stored->get($group)?->firstWhere('key', $key);
                $result[$group][$key] = $storedSetting?->casted_value
                    ?? $definition['default']
                    ?? $school->{$key} ?? null;
            }
        }

        foreach ($stored as $group => $items) {
            if (! isset($result[$group])) {
                $result[$group] = [];
            }
            foreach ($items as $item) {
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

            return SchoolSetting::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->where('group', 'regional')
                ->where('key', 'currency')
                ->firstOrFail();
        }

        return DB::transaction(function () use ($school, $group, $key, $value, $type, $isPublic) {
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
