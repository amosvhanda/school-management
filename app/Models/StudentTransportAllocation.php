<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransportAllocation extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = [
        'student_id', 'route_id', 'pickup_point', 'status', 'allocated_at',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'student';
    }

    protected function casts(): array
    {
        return ['allocated_at' => 'date'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
