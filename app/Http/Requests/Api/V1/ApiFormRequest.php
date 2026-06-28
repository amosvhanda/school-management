<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiFormRequest extends FormRequest
{
    /**
     * Provide Scribe with body parameter metadata derived from validation rules.
     */
    public function bodyParameters(): array
    {
        $parameters = [];

        foreach (array_keys($this->rules()) as $field) {
            $parameters[$field] = [
                'description' => ucfirst(str_replace(['.', '_'], ' ', $field)),
            ];
        }

        return $parameters;
    }
}
