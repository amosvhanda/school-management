<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'school_id',
    ];

    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get setting value with type casting
     */
    public function getValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
            case 'integer':
                return is_numeric($value) ? (int)$value : 0;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Set setting value with type conversion
     */
    public function setValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            case 'json':
                $this->attributes['value'] = json_encode($value);
                break;
            default:
                $this->attributes['value'] = (string)$value;
        }
    }

    /**
     * Get setting by key (scoped to current user's school).
     */
    public static function get($key, $default = null)
    {
        $schoolId = Auth::user()?->school_id;
        if ($schoolId === null) {
            return $default;
        }
        $setting = self::withoutGlobalScopes()->where('school_id', $schoolId)->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting by key (per-school; requires school context).
     */
    public static function set($key, $value, $type = 'string', $category = 'general')
    {
        $schoolId = Auth::user()?->school_id;
        if ($schoolId === null) {
            throw new \RuntimeException('Settings require a school context. User must belong to a school.');
        }

        // Handle nested keys like "academic.currentTerm"
        $parts = explode('.', $key);
        if (count($parts) > 1) {
            $category = $parts[0];
            $key = $parts[1];
        }

        return self::withoutGlobalScopes()->updateOrCreate(
            ['school_id' => $schoolId, 'key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'category' => $category,
                'school_id' => $schoolId,
            ]
        );
    }
}
