<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class QuestionBankItem extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'question_text', 'question_type', 'options',
        'correct_answer', 'difficulty', 'tags', 'marks',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'tags' => 'array'];
    }
}
