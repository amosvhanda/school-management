<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Requests\Api\V1\ApiFormRequest;

class ResetPasswordRequest extends ApiFormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => $this->passwordRules(),
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
