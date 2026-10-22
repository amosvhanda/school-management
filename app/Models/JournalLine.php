<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use IsolatesViaSchoolRelation;

    public $timestamps = false;

    protected $fillable = [
        'journal_entry_id', 'account_id', 'debit', 'credit', 'currency', 'memo',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'entry';
    }

    protected function casts(): array
    {
        return ['debit' => 'decimal:2', 'credit' => 'decimal:2'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
