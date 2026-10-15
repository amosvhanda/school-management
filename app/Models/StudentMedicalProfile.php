<?php

namespace App\Models;

use App\Models\Concerns\IsolatesViaSchoolRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMedicalProfile extends Model
{
    use IsolatesViaSchoolRelation;

    protected $guarded = [];

    public static function schoolIsolationRelation(): string
    {
        return 'student';
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
