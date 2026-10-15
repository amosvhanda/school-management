<?php

namespace App\Models\Concerns;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

trait Auditable
{
    public static function bootAuditable(): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        Event::listen("eloquent.created: {$modelClass}", function (Model $model) {
            try {
                app(AuditService::class)->logModelEvent($model, 'created');
            } catch (\Throwable $e) {
                report($e);
            }
        });

        Event::listen("eloquent.updated: {$modelClass}", function (Model $model) {
            if ($model->getChanges() === []) {
                return;
            }

            try {
                app(AuditService::class)->logModelEvent($model, 'updated');
            } catch (\Throwable $e) {
                report($e);
            }
        });

        Event::listen("eloquent.deleted: {$modelClass}", function (Model $model) {
            try {
                app(AuditService::class)->logModelEvent($model, 'deleted');
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    public function getAuditModule(): string
    {
        return config('audit.model_modules')[static::class] ?? 'system';
    }
}
