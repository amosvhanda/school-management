<?php

namespace App\Http\Requests\Api\V1\Assistant;

use App\Http\Requests\Api\V1\ApiFormRequest;

class ChatRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'conversation_id' => ['nullable', 'uuid'],
        ];
    }
}
