<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecureDocumentAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_id', 'user_id', 'action', 'ip_address', 'accessed_at',
    ];

    protected function casts(): array
    {
        return ['accessed_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'document_id');
    }
}
