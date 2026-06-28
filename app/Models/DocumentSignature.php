<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignature extends Model
{
    protected $fillable = [
        'document_id', 'signer_id', 'signer_role', 'signature_hash',
        'ip_address', 'signed_at',
    ];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SignableDocument::class, 'document_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }
}
