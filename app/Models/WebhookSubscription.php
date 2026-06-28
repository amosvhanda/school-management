<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'event_type', 'target_url', 'secret_hash', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
