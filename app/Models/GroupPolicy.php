<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupPolicy extends Model
{
    protected $fillable = ['parent_school_id', 'policy_code', 'name', 'rules', 'enforce_on_branches'];

    protected function casts(): array
    {
        return ['rules' => 'array', 'enforce_on_branches' => 'boolean'];
    }

    public function parentSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'parent_school_id');
    }
}
