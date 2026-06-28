<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrossSchoolTransfer extends Model
{
    protected $fillable = [
        'student_id', 'from_school_id', 'to_school_id', 'status', 'requested_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
