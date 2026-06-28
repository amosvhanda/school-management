<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingScale extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'grade',
        'min_score',
        'max_score',
        'description',
        'order',
    ];

    protected function casts(): array
    {
        return [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'order' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the grade letter for a given score
     */
    public static function getGradeForScore($schoolId, $score): ?string
    {
        return static::where('school_id', $schoolId)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('order', 'desc')
            ->value('grade');
    }
}
