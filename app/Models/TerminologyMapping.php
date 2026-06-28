<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminologyMapping extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'system_key',
        'custom_label',
        'locale',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
