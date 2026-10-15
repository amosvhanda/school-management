<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtResponse extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = ['session_id', 'question_id', 'answer', 'is_correct'];

    public static function schoolIsolationRelation(): string
    {
        return 'session';
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CbtExamSession::class, 'session_id');
    }

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
