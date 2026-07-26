<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use BelongsToSchool, Auditable, HasFactory;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'leave_type_id',
        'requested_by',
        'type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getAuditIdentifier(): string
    {
        $teacher = $this->relationLoaded('teacher')
            ? $this->teacher
            : $this->teacher()->first();

        $who = $teacher
            ? trim((string) ($teacher->name ?: ($teacher->first_name.' '.$teacher->last_name)))
            : 'staff member';

        $type = $this->type ? str_replace('_', ' ', (string) $this->type) : 'leave';
        $from = $this->start_date?->format('j M Y');
        $to = $this->end_date?->format('j M Y');

        if ($from && $to) {
            return ucfirst($type)." leave for {$who} ({$from} – {$to})";
        }

        return ucfirst($type)." leave for {$who}";
    }
}
