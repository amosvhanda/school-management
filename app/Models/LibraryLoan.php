<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryLoan extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = [
        'book_id', 'student_id', 'library_member_id', 'borrowed_at', 'due_at',
        'returned_at', 'fine_amount', 'status',
    ];

    public static function schoolIsolationRelation(): string
    {
        return 'book';
    }

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
            'fine_amount' => 'decimal:2',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(LibraryMember::class, 'library_member_id');
    }
}
