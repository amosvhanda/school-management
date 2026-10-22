<?php

namespace App\Models\Scopes;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Tenancy\CurrentSchoolId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class UserSchoolScope implements Scope
{
    private static bool $resolvingUser = false;

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$resolvingUser) {
            return;
        }

        self::$resolvingUser = true;

        try {
            $user = Auth::user();
            if (! $user instanceof User || $user->role === UserRole::SuperAdmin) {
                return;
            }

            $schoolId = CurrentSchoolId::resolve();
            $table = $model->getTable();

            if ($schoolId === null) {
                $builder->where($table.'.id', (int) $user->id);

                return;
            }

            $builder->where(function (Builder $query) use ($table, $schoolId, $user) {
                $query->where($table.'.school_id', $schoolId)
                    ->orWhere($table.'.id', (int) $user->id);
            });
        } finally {
            self::$resolvingUser = false;
        }
    }
}
