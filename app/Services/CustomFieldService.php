<?php

namespace App\Services;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomFieldService
{
    public function listForEntity(School $school, string $entityType): Collection
    {
        return CustomField::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('entity_type', $entityType)
            ->orderBy('sort_order')
            ->get();
    }

    public function create(School $school, array $data): CustomField
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        return CustomField::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'entity_type' => $data['entity_type'],
            'name' => $data['name'],
            'slug' => $slug,
            'field_type' => $data['field_type'] ?? 'text',
            'options' => $data['options'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(CustomField $customField, array $data): CustomField
    {
        $customField->update([
            'name' => $data['name'] ?? $customField->name,
            'slug' => $data['slug'] ?? $customField->slug,
            'field_type' => $data['field_type'] ?? $customField->field_type,
            'options' => $data['options'] ?? $customField->options,
            'is_required' => $data['is_required'] ?? $customField->is_required,
            'is_active' => $data['is_active'] ?? $customField->is_active,
            'sort_order' => $data['sort_order'] ?? $customField->sort_order,
        ]);

        return $customField;
    }

    public function validateAndSync(Model $entity, string $entityType, array $customFields): void
    {
        $definitions = CustomField::withoutGlobalScopes()
            ->where('school_id', $entity->school_id)
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->get()
            ->keyBy('slug');

        $errors = [];

        foreach ($definitions as $slug => $field) {
            if ($field->is_required && (! array_key_exists($slug, $customFields) || $customFields[$slug] === null || $customFields[$slug] === '')) {
                $errors[$slug] = ["The {$field->name} field is required."];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($customFields as $slug => $value) {
            $field = $definitions->get($slug);
            if (! $field) {
                continue;
            }

            CustomFieldValue::updateOrCreate(
                [
                    'custom_field_id' => $field->id,
                    'entity_type' => $entity->getMorphClass(),
                    'entity_id' => $entity->id,
                ],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }
    }
}
