<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentResponse extends Model
{
    use IsolatesViaSchoolRelation;

    protected $guarded = [];

    public static function schoolIsolationRelation(): string
    {
        return 'form';
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(ConsentForm::class, 'form_id');
    }
}
