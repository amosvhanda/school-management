<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomFieldValue extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = [
        'custom_field_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'customField';
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
