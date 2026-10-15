<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelAllocation extends Model
{
    use Auditable, IsolatesViaSchoolRelation;

    protected $fillable = [
        'student_id', 'bed_id', 'allocated_at', 'left_at', 'status',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'student';
    }

    protected function casts(): array
    {
        return ['allocated_at' => 'date', 'left_at' => 'date'];
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(HostelBed::class, 'bed_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
