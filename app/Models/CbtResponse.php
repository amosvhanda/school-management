<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CbtResponse extends Model
{
    protected $fillable = ['session_id', 'question_id', 'answer', 'is_correct'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
