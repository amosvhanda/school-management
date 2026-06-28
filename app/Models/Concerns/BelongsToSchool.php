<?php

namespace App\Models\Concerns;

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
}
