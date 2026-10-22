<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'certificate_template_id', 'student_id', 'certificate_type', 'title',
        'verification_code', 'content_html', 'metadata',
        'issued_by', 'issued_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
