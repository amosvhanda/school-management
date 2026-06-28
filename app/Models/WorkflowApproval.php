<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowApproval extends Model
{
    public $timestamps = false;

    protected $fillable = ['instance_id', 'step_order', 'approver_id', 'action', 'comments', 'acted_at'];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime'];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
