<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAntiCheatLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['session_id', 'event_type', 'metadata', 'logged_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'logged_at' => 'datetime'];
    }
}
