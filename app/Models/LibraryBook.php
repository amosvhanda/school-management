<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryBook extends Model
{
    use Auditable, BelongsToSchool;

    protected $fillable = [
        'school_id', 'isbn', 'title', 'author', 'total_copies', 'available_copies',
        'category', 'rack_number', 'shelf_number',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(LibraryLoan::class, 'book_id');
    }
}
