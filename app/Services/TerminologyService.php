<?php

namespace App\Services;

use App\Models\School;
use App\Models\TerminologyMapping;
use Illuminate\Support\Collection;

class TerminologyService
{
    public function getForSchool(School $school, string $locale = 'en'): array
    {
        $defaults = config('school.defaults', []);

        $overrides = TerminologyMapping::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('locale', $locale)
            ->pluck('custom_label', 'system_key')
            ->all();

        return array_merge($defaults, $overrides);
    }

    public function setMapping(School $school, string $systemKey, string $customLabel, string $locale = 'en'): TerminologyMapping
    {
        return TerminologyMapping::withoutGlobalScopes()->updateOrCreate(
            [
                'school_id' => $school->id,
                'system_key' => $systemKey,
                'locale' => $locale,
            ],
            ['custom_label' => $customLabel]
        );
    }

    public function bulkSet(School $school, array $mappings, string $locale = 'en'): Collection
    {
        return collect($mappings)->map(
            fn (string $label, string $key) => $this->setMapping($school, $key, $label, $locale)
        );
    }

    public function seedDefaults(School $school, string $locale = 'en'): void
    {
        foreach (config('school.defaults', []) as $key => $label) {
            TerminologyMapping::withoutGlobalScopes()->firstOrCreate(
                [
                    'school_id' => $school->id,
                    'system_key' => $key,
                    'locale' => $locale,
                ],
                ['custom_label' => $label]
            );
        }
    }
}
