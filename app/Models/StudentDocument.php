<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'uploaded_by',
        'name',
        'type',
        'path',
        'mime_type',
        'size',
        'is_favourite',
        'verified_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_favourite' => 'boolean',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
