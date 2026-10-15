<?php

namespace App\Services\Tenancy;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TenantSwitchService
{
    /**
     * Ensure the user's active school has a membership row.
     */
    public function ensureMembership(User $user, ?int $schoolId = null, bool $isDefault = false): ?SchoolUserMembership
    {
        $schoolId = $schoolId ?? $user->school_id;
        if (! $schoolId || $user->role === UserRole::SuperAdmin) {
            return null;
        }

        return SchoolUserMembership::firstOrCreate(
            [
                'school_id' => $schoolId,
                'user_id' => $user->id,
            ],
            [
                'role' => $user->roleValue(),
                'is_default' => $isDefault || SchoolUserMembership::where('user_id', $user->id)->doesntExist(),
            ],
        );
    }

    /**
     * @return Collection<int, array{id:int,name:string,code:?string,status:string,subtitle?:string,is_current:bool,is_default:bool}>
     */
    public function availableSchools(User $user): Collection
    {
        if ($user->role === UserRole::SuperAdmin) {
            return School::query()
                ->where('status', '!=', 'deleted')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'status', 'city', 'branch_type', 'parent_school_id'])
                ->map(fn (School $school) => [
                    'id' => $school->id,
                    'name' => $school->name,
                    'code' => $school->code,
                    'status' => $school->status,
                    'subtitle' => $school->city
                        ?: ($school->code
                            ?: ($school->branch_type === 'branch' && $school->parent_school_id
                                ? 'Branch campus'
                                : 'Primary campus')),
                    'is_current' => (int) $user->school_id === (int) $school->id,
                    'is_default' => false,
                ])
                ->values();
        }

        $this->ensureMembership($user, $user->school_id, true);

        return SchoolUserMembership::query()
            ->where('user_id', $user->id)
            ->with('school:id,name,code,status')
            ->get()
            ->filter(fn (SchoolUserMembership $m) => $m->school !== null)
            ->map(fn (SchoolUserMembership $m) => [
                'id' => $m->school->id,
                'name' => $m->school->name,
                'code' => $m->school->code,
                'status' => $m->school->status,
                'is_current' => (int) $user->school_id === (int) $m->school_id,
                'is_default' => (bool) $m->is_default,
            ])
            ->values();
    }

    public function userBelongsToSchool(User $user, int $schoolId): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ((int) $user->school_id === $schoolId) {
            return true;
        }

        return SchoolUserMembership::query()
            ->where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->exists();
    }

    /**
     * @throws ValidationException
     */
    public function switchTo(User $user, int $schoolId): User
    {
        if ($user->role === UserRole::SuperAdmin) {
            $school = School::query()->find($schoolId);
            if (! $school || $school->status === 'deleted') {
                throw ValidationException::withMessages([
                    'school_id' => ['That school is not available.'],
                ]);
            }
            if ($school->status !== 'active') {
                throw ValidationException::withMessages([
                    'school_id' => ['That school is currently suspended.'],
                ]);
            }

            $user->update(['school_id' => $schoolId]);

            return $user->fresh();
        }

        if (! $this->userBelongsToSchool($user, $schoolId)) {
            throw ValidationException::withMessages([
                'school_id' => ['You do not have access to that school.'],
            ]);
        }

        $school = School::find($schoolId);
        if (! $school || $school->status === 'deleted') {
            throw ValidationException::withMessages([
                'school_id' => ['That school is not available.'],
            ]);
        }

        if ($school->status !== 'active') {
            throw ValidationException::withMessages([
                'school_id' => ['That school is currently suspended.'],
            ]);
        }

        $membership = $this->ensureMembership($user, $schoolId);
        $user->update(['school_id' => $schoolId]);

        if ($membership) {
            SchoolUserMembership::where('user_id', $user->id)->update(['is_default' => false]);
            $membership->update(['is_default' => true]);
        }

        return $user->fresh();
    }
}
