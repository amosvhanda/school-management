<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SchoolViaRelationScope;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait IsolatesViaSchoolRelation
{
    public static function bootIsolatesViaSchoolRelation(): void
    {
        static::addGlobalScope(new SchoolViaRelationScope(static::schoolIsolationRelation()));
    }

    abstract public static function schoolIsolationRelation(): string;
}
