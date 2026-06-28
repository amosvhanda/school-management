<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class ChangePasswordRequest extends ApiFormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],
            'new_password' => $this->passwordRules(),
            'new_password_confirmation' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            if (! $user || ! Hash::check($this->old_password, $user->password)) {
                $validator->errors()->add(
                    'old_password',
                    __('The provided password does not match your current password.')
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'password' => $this->new_password,
            'password_confirmation' => $this->new_password_confirmation,
            'current_password' => $this->old_password,
        ]);
    }
}
