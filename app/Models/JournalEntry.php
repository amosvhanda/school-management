<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'reference', 'entry_date', 'description', 'status', 'currency', 'created_by',
    ];

    protected function casts(): array
    {
        return ['entry_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
