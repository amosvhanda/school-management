<?php

namespace App\Models\Concerns;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasCustomFields
{
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'entity');
    }

    public function getCustomFieldsAttribute(): array
    {
        $entityType = $this->getCustomFieldEntityType();

        $fields = CustomField::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $values = $this->customFieldValues()
            ->whereIn('custom_field_id', $fields->pluck('id'))
            ->get()
            ->keyBy('custom_field_id');

        return $fields->mapWithKeys(function (CustomField $field) use ($values) {
            return [$field->slug => $values->get($field->id)?->value];
        })->all();
    }

    public function syncCustomFields(array $customFields): void
    {
        if ($customFields === []) {
            return;
        }

        $entityType = $this->getCustomFieldEntityType();

        $definitions = CustomField::query()
            ->where('entity_type', $entityType)
            ->whereIn('slug', array_keys($customFields))
            ->get()
            ->keyBy('slug');

        foreach ($customFields as $slug => $value) {
            $field = $definitions->get($slug);
            if (! $field) {
                continue;
            }

            $this->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $field->id],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }
    }

    abstract protected function getCustomFieldEntityType(): string;
}
