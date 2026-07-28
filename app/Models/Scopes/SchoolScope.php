<?php

namespace App\Models\Scopes;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Platform operators must see across tenants.
        if ($user && $user->role === UserRole::SuperAdmin) {
            return;
        }

        // Authenticated school users are always scoped to their active school.
        if ($user && $user->school_id !== null) {
            $builder->where($model->getTable().'.school_id', (int) $user->school_id);

            return;
        }

        // Queue / console tenant context (SetTenantContext) when no school user is authenticated.
        $fromConfig = config('tenancy.current_school_id');
        if (is_numeric($fromConfig) && (int) $fromConfig > 0) {
            $builder->where($model->getTable().'.school_id', (int) $fromConfig);
        }
    }
}
