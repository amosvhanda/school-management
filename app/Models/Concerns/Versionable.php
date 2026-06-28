<?php

namespace App\Models\Concerns;

use App\Services\Platform\RecordVersionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

trait Versionable
{
    public static function bootVersionable(): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        Event::listen("eloquent.updated: {$modelClass}", function (Model $model) {
            if ($model->getChanges() === [] || app()->runningInConsole()) {
                return;
            }

            app(RecordVersionService::class)->snapshot(
                $model,
                Auth::user(),
                'auto_snapshot',
            );
        });
    }
}
