<?php

namespace App\Http\Requests\Platform;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class SendHubMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Add any internal RBAC permission check here if necessary
        return true;
    }

    public function rules(): array
    {
        $roleValues = array_map(fn (UserRole $role) => $role->value, UserRole::cases());
        $allowedTypes = array_merge(['individual', 'all_staff'], $roleValues);

        return [
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,sms,whatsapp,push',
            'audience_type' => 'required|in:'.implode(',', $allowedTypes),
            'recipient_ids' => 'required_if:audience_type,individual|array',
            'recipient_ids.*' => 'integer',
        ];
    }
}
