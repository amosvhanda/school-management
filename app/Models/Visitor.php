<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'phone', 'id_number', 'purpose',
        'host_user_id', 'student_id', 'check_in_at', 'check_out_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
