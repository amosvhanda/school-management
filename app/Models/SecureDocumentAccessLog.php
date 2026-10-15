<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecureDocumentAccessLog extends Model
{
    use IsolatesViaSchoolRelation;

    public $timestamps = false;

    protected $fillable = [
        'document_id', 'user_id', 'action', 'ip_address', 'accessed_at',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'document';
    }

    protected function casts(): array
    {
        return ['accessed_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'document_id');
    }
}
