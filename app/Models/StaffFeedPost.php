<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffFeedPost extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'author_id', 'body', 'attachments', 'visibility',
    ];

    protected function casts(): array
    {
        return ['attachments' => 'array'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(StaffFeedComment::class, 'post_id');
    }
}
