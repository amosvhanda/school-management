<?php

namespace App\Http\Controllers\Platform\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ResolvesPlatformSchoolScope
{
    /**
     * Super admin: all schools (null), or a specific school via ?school_id=.
     * School users: their own school_id only.
     */
    protected function platformSchoolId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            $requested = $request->integer('school_id') ?: null;

            return $requested ?: null;
        }

        return $user->school_id ? (int) $user->school_id : null;
    }

    /**
     * School id required for create/update actions.
     * Super admin must pass school_id explicitly.
     */
    protected function requirePlatformSchoolId(Request $request): int
    {
        $user = $request->user();
        if ($user instanceof User && $user->isSuperAdmin()) {
            $schoolId = $request->integer('school_id') ?: null;
            if (! $schoolId) {
                throw ValidationException::withMessages([
                    'school_id' => 'Select a school for this platform action.',
                ]);
            }

            return $schoolId;
        }

        $schoolId = $user?->school_id;
        if (! $schoolId) {
            throw ValidationException::withMessages([
                'school_id' => 'No school is associated with this account.',
            ]);
        }

        return (int) $schoolId;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function scopeToPlatformSchool(Builder $query, Request $request, string $column = 'school_id'): Builder
    {
        $schoolId = $this->platformSchoolId($request);

        return $query->when($schoolId, fn (Builder $q) => $q->where($column, $schoolId));
    }
}
