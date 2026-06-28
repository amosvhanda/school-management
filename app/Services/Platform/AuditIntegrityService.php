<?php

namespace App\Services\Platform;

use App\Models\AuditLog;

class AuditIntegrityService
{
    public function sealLog(AuditLog $log): AuditLog
    {
        $previous = AuditLog::where('school_id', $log->school_id)
            ->where('id', '<', $log->id)
            ->whereNotNull('integrity_hash')
            ->orderByDesc('id')
            ->first();

        $previousHash = $previous?->integrity_hash ?? 'GENESIS';
        $payload = json_encode([
            'id' => $log->id,
            'school_id' => $log->school_id,
            'module' => $log->module,
            'action' => $log->action,
            'user_id' => $log->user_id,
            'created_at' => $log->created_at?->toIso8601String(),
            'previous_hash' => $previousHash,
        ]);

        $log->update([
            'previous_hash' => $previousHash,
            'integrity_hash' => hash('sha256', $payload),
        ]);

        return $log->fresh();
    }

    public function verifyChain(?int $schoolId = null, int $limit = 500): array
    {
        $query = AuditLog::query()->orderBy('id');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $logs = $query->limit($limit)->get();
        $broken = [];
        $expectedPrevious = 'GENESIS';

        foreach ($logs as $log) {
            if (! $log->integrity_hash) {
                continue;
            }

            if ($log->previous_hash !== $expectedPrevious) {
                $broken[] = ['id' => $log->id, 'reason' => 'previous_hash_mismatch'];
            }

            $payload = json_encode([
                'id' => $log->id,
                'school_id' => $log->school_id,
                'module' => $log->module,
                'action' => $log->action,
                'user_id' => $log->user_id,
                'created_at' => $log->created_at?->toIso8601String(),
                'previous_hash' => $log->previous_hash,
            ]);

            $computed = hash('sha256', $payload);
            if ($log->integrity_hash !== $computed) {
                $broken[] = ['id' => $log->id, 'reason' => 'integrity_hash_mismatch'];
            }

            $expectedPrevious = $log->integrity_hash;
        }

        $sealedCount = $logs->whereNotNull('integrity_hash')->count();

        return [
            'verified' => count($broken) === 0,
            'checked' => $sealedCount,
            'broken_links' => $broken,
        ];
    }
}
