<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entity_type,
            'name' => $this->name,
            'slug' => $this->slug,
            'field_type' => $this->field_type,
            'options' => $this->options,
            'is_required' => $this->is_required,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
