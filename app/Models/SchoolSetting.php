<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'group',
        'key',
        'value',
        'type',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
        'is_public' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function getCastedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->attributes['value'], FILTER_VALIDATE_BOOLEAN),
            'integer', 'number' => is_numeric($this->attributes['value']) ? (int) $this->attributes['value'] : 0,
            'float', 'decimal' => is_numeric($this->attributes['value']) ? (float) $this->attributes['value'] : 0.0,
            'json' => json_decode($this->attributes['value'] ?? 'null', true),
            default => $this->attributes['value'],
        };
    }

    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
