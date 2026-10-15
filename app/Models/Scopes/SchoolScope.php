<?php

namespace App\Models\Scopes;

use App\Support\Tenancy\CurrentSchoolId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = CurrentSchoolId::resolve();
        $column = $model->getTable().'.school_id';

        if ($schoolId !== null) {
            $builder->where($column, $schoolId);

            return;
        }

        if (CurrentSchoolId::shouldFailClosed()) {
            $builder->whereRaw('0 = 1');
        }
    }
}
