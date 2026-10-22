<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Policy extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'title', 'category', 'content', 'effective_date',
        'review_date', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'review_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
