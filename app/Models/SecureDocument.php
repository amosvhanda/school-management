<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecureDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'vault_type', 'title', 'encrypted_payload',
        'content_hash', 'access_roles', 'uploaded_by', 'exam_id',
    ];

    protected function casts(): array
    {
        return ['access_roles' => 'array'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(SecureDocumentAccessLog::class, 'document_id');
    }
}
