<?php

namespace App\Services\Platform;

use App\Models\ExamResult;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MarkModerationLockService
{
    public function lockExamResults(int $examId, int $schoolId): int
    {
        return ExamResult::where('exam_id', $examId)
            ->where('school_id', $schoolId)
            ->where('status', 'approved')
            ->update(['is_locked' => true, 'locked_at' => now()]);
    }

    public function assertEditable(ExamResult $result): void
    {
        if ($result->is_locked || $result->status === 'approved') {
            throw new AccessDeniedHttpException('Exam marks are locked after moderation approval.');
        }
    }
}
