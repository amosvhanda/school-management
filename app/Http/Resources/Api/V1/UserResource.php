<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->roleValue(),
            'avatar_url' => $this->avatar_url,
            'status' => $this->status ?? 'active',
            'school_id' => $this->school_id,
            'must_change_password' => (bool) $this->must_change_password,
            'platform_terms_accepted' => $this->hasAcceptedCurrentPlatformTerms(),
            'platform_terms_version' => $this->platform_terms_version,
            'platform_terms_accepted_at' => $this->platform_terms_accepted_at?->toIso8601String(),
            'platform_terms_required_version' => (string) config('platform_terms.version'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
