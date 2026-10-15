<?php

namespace App\Models\Concerns;

use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Support\Tenancy\CurrentSchoolId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Event;

trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        $modelClass::addGlobalScope(new SchoolScope);

        Event::listen("eloquent.creating: {$modelClass}", function (Model $model) {
            if ($model->school_id !== null) {
                return;
            }

            $schoolId = CurrentSchoolId::resolve();
            if ($schoolId !== null) {
                $model->school_id = $schoolId;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeWithoutSchoolScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(SchoolScope::class);
    }

    /**
     * Query a single school while bypassing the request-level tenant scope.
     * Use from platform/admin services that already authorized a school id.
     */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->withoutGlobalScope(SchoolScope::class)
            ->where($this->getTable().'.school_id', $schoolId);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->newModelQuery();
        $field = $field ?? $this->getRouteKeyName();
        $schoolId = CurrentSchoolId::resolve();

        if ($schoolId !== null) {
            $query->where($this->getTable().'.school_id', $schoolId);
        } elseif (CurrentSchoolId::shouldFailClosed()) {
            $query->whereRaw('0 = 1');
        }

        return $query->where($field, $value)->first();
    }
}
