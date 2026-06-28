<?php

namespace App\Http\Resources;

use App\Support\AuditLabels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LoginHistory */
class LoginHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->whenLoaded('user');
        $actorName = $user?->name ?? $this->email ?? 'Unknown user';

        return [
            'id' => $this->id,
            'event' => $this->event,
            'event_label' => AuditLabels::action($this->event),
            'summary' => match ($this->event) {
                'login' => "{$actorName} signed in successfully",
                'logout' => "{$actorName} signed out",
                'failed_login' => "Failed sign-in attempt for {$this->email}",
                default => AuditLabels::action($this->event).' — '.$actorName,
            },
            'email' => $this->email,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => is_object($user->role) ? $user->role->value : $user->role,
            ] : null,
            'actor' => [
                'id' => $user?->id,
                'name' => $actorName,
                'email' => $this->email ?? $user?->email,
                'role' => $user ? (is_object($user->role) ? $user->role->value : $user->role) : null,
            ],
            'failure_reason' => $this->failure_reason,
            'token_name' => $this->token_name,
            'context' => [
                'ip_address' => $this->ip_address,
                'device_type' => $this->device_type,
                'platform' => $this->platform,
                'location' => $this->location,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
