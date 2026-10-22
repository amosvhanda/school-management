<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicVisit extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'visit_date', 'complaint', 'diagnosis',
        'treatment', 'nurse_user_id',
    ];

    protected function casts(): array
    {
        return ['visit_date' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_user_id');
    }
}
