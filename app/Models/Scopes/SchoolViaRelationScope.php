<?php

namespace App\Models\Scopes;

use App\Support\Tenancy\CurrentSchoolId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolViaRelationScope implements Scope
{
    public function __construct(private readonly string $relation) {}

    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = CurrentSchoolId::resolve();

        if ($schoolId !== null) {
            $builder->whereHas($this->relation, function (Builder $query) use ($schoolId) {
                $related = $query->getModel();
                $query->withoutGlobalScopes()
                    ->where($related->getTable().'.school_id', $schoolId);
            });

            return;
        }

        if (CurrentSchoolId::shouldFailClosed()) {
            $builder->whereRaw('0 = 1');
        }
    }
}
