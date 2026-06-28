<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplate extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'name',
        'description',
        'category',
        'frequency',
        'recipients',
        'parameters',
        'school_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
        'recipients' => 'array',
        'parameters' => 'array',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
