<?php

namespace App\Services\Platform;

use App\Models\RecordVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordVersionService
{
    public function snapshot(Model $model, ?User $user = null, ?string $reason = null): RecordVersion
    {
        $latest = RecordVersion::where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->max('version_number');

        return RecordVersion::create([
            'school_id' => $model->school_id ?? $user?->school_id,
            'versionable_type' => $model->getMorphClass(),
            'versionable_id' => $model->getKey(),
            'version_number' => ((int) $latest) + 1,
            'snapshot' => $model->getAttributes(),
            'changed_by' => $user?->id,
            'change_reason' => $reason,
            'created_at' => now(),
        ]);
    }

    public function history(Model $model)
    {
        return RecordVersion::where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->orderByDesc('version_number')
            ->with('changedBy:id,name')
            ->get();
    }
}
