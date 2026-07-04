<?php

namespace App\Models\Concerns;

use App\Enums\UserRole;
use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        $modelClass::addGlobalScope(new SchoolScope());

        Event::listen("eloquent.creating: {$modelClass}", function (Model $model) {
            if ($model->school_id !== null) {
                return;
            }

            $user = Auth::user();
            if ($user?->school_id) {
                $model->school_id = $user->school_id;
            }
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->newModelQuery();
        $field = $field ?? $this->getRouteKeyName();
        $user = Auth::user();

        if ($user && $user->role !== UserRole::SuperAdmin && $user->school_id !== null) {
            $query->where($this->getTable() . '.school_id', $user->school_id);
        }

        return $query->where($field, $value)->first();
    }

    public function resolveChildRouteBinding($childType, $value, $field = null)
    {
        return $this->newModelQuery()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
