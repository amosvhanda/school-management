<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ConsentForm extends Model
{
    use Auditable, BelongsToSchool;

    protected $guarded = [];
}
