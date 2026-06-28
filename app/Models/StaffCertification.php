<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StaffCertification extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'certification_name', 'issuer', 'issued_on', 'expires_on',
    ];

    protected function casts(): array
    {
        return ['issued_on' => 'date', 'expires_on' => 'date'];
    }
}
