<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    use BelongsToSchool;

    public const ENTITY_STUDENT = 'student';

    public const ENTITY_TEACHER = 'teacher';

    public const ENTITY_STAFF = 'staff';

    public const FIELD_TYPES = [
        'text', 'textarea', 'number', 'date', 'boolean', 'select', 'multiselect', 'email', 'phone',
    ];

    protected $fillable = [
        'school_id',
        'entity_type',
        'name',
        'slug',
        'field_type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }
}
