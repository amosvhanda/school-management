<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentResponse extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = [
        'form_id', 'parent_user_id', 'student_id', 'status', 'responded_at', 'notes',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'form';
    }

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(ConsentForm::class, 'form_id');
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
