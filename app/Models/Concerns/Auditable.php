<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

trait Auditable
{
    public static function bootAuditable(): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        Event::listen("eloquent.created: {$modelClass}", function (Model $model) {
            app(\App\Services\AuditService::class)->logModelEvent($model, 'created');
        });

        Event::listen("eloquent.updated: {$modelClass}", function (Model $model) {
            if ($model->getChanges() !== []) {
                app(\App\Services\AuditService::class)->logModelEvent($model, 'updated');
            }
        });

        Event::listen("eloquent.deleted: {$modelClass}", function (Model $model) {
            app(\App\Services\AuditService::class)->logModelEvent($model, 'deleted');
        });
    }

    public function getAuditModule(): string
    {
        return config('audit.model_modules')[static::class] ?? 'system';
    }
}
