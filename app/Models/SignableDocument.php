<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignableDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'title', 'document_type', 'content', 'status',
        'created_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class, 'document_id');
    }
}
