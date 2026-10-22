<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentForm extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'title', 'content', 'target_audience', 'due_date',
        'requires_signature', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'requires_signature' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ConsentResponse::class, 'form_id');
    }
}
