<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $table = 'login_history';

    protected $fillable = [
        'school_id', 'user_id', 'email', 'event', 'ip_address', 'user_agent',
        'device_type', 'platform', 'location', 'token_name', 'failure_reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
