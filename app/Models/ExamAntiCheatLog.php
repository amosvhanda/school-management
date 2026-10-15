<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAntiCheatLog extends Model
{
    use IsolatesViaSchoolRelation;

    public $timestamps = false;

    public static function schoolIsolationRelation(): string
    {
        return 'session';
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CbtExamSession::class, 'session_id');
    }

    protected $fillable = ['session_id', 'event_type', 'metadata', 'logged_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'logged_at' => 'datetime'];
    }
}
