<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentCategory extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'subject_id', 'name', 'type', 'weight', 'is_active'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(ContinuousAssessment::class, 'category_id');
    }
}
