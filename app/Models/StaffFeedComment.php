<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffFeedComment extends Model
{
    use IsolatesViaSchoolRelation;

    protected $fillable = ['post_id', 'author_id', 'body'];

    public static function schoolIsolationRelation(): string
    {
        return 'post';
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(StaffFeedPost::class, 'post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
