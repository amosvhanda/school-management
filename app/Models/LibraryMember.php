<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryMember extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'member_type',
        'member_id',
        'member_number',
        'name',
        'email',
        'phone',
        'status',
        'joined_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joined_on' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
