<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelAllocation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['allocated_at' => 'date', 'left_at' => 'date'];
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(HostelBed::class, 'bed_id');
    }
}
