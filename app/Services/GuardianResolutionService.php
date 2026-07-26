<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\User;

/**
 * Canonical User ↔ Guardian linking.
 *
 * Prefer guardians.user_id; fall back to school-scoped email (then phone) and persist the link.
 */
class GuardianResolutionService
{
    public function __construct(private GuardianService $guardians) {}

    public function resolveForUser(User $user, bool $persistLink = true): ?Guardian
    {
        $guardian = Guardian::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->where('user_id', $user->id)
            ->first();

        if ($guardian) {
            return $guardian;
        }

        if (! $user->email && ! $user->phone) {
            return null;
        }

        $guardian = Guardian::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->where(function ($q) use ($user) {
                if ($user->email) {
                    $q->where('email', $user->email);
                }
                if ($user->phone) {
                    if ($user->email) {
                        $q->orWhere('phone', $user->phone);
                    } else {
                        $q->where('phone', $user->phone);
                    }
                }
            })
            ->orderByRaw('case when email = ? then 0 else 1 end', [$user->email ?? ''])
            ->first();

        if ($guardian && $persistLink && (int) $guardian->user_id !== (int) $user->id) {
            $guardian->forceFill([
                'user_id' => $user->id,
                'email' => $user->email ?: $guardian->email,
                'phone' => $user->phone ?: $guardian->phone,
            ])->save();

            // Keep parent_student in sync for every linked child.
            $guardian->loadMissing('students:id');
            foreach ($guardian->students as $student) {
                $this->guardians->syncParentUserLink($guardian, (int) $student->id, [
                    'relationship' => $student->pivot?->relationship ?? $guardian->relationship ?? 'parent',
                    'is_primary' => (bool) ($student->pivot?->is_primary ?? $guardian->is_primary),
                ]);
            }
        }

        return $guardian;
    }
}
