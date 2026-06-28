<?php

namespace App\Services\Enterprise;

use App\Models\CbtExamSession;
use App\Models\CbtResponse;
use App\Models\ExamAntiCheatLog;
use App\Models\QuestionBankItem;
use App\Models\RemarkRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExaminationEnterpriseService
{
    public function addQuestion(int $schoolId, array $data): QuestionBankItem
    {
        return QuestionBankItem::create(array_merge($data, ['school_id' => $schoolId]));
    }

    public function generateRandomPaper(int $schoolId, int $subjectId, int $count, array $filters = []): Collection
    {
        $query = QuestionBankItem::where('school_id', $schoolId)->where('subject_id', $subjectId);

        if (! empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        return $query->inRandomOrder()->limit($count)->get();
    }

    public function startCbtSession(int $schoolId, int $studentId, array $questionIds, ?int $examId = null): CbtExamSession
    {
        return CbtExamSession::create([
            'school_id' => $schoolId,
            'exam_id' => $examId,
            'student_id' => $studentId,
            'question_ids' => $questionIds,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    public function submitCbtSession(CbtExamSession $session, array $answers): CbtExamSession
    {
        return DB::transaction(function () use ($session, $answers) {
            $score = 0;
            $max = 0;

            foreach ($answers as $answer) {
                $question = QuestionBankItem::find($answer['question_id']);
                if (! $question) {
                    continue;
                }
                $isCorrect = $question->correct_answer !== null
                    && strtolower(trim($answer['answer'])) === strtolower(trim($question->correct_answer));

                CbtResponse::create([
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                    'answer' => $answer['answer'],
                    'is_correct' => $isCorrect,
                ]);

                $max += $question->marks;
                if ($isCorrect) {
                    $score += $question->marks;
                }
            }

            $session->update([
                'submitted_at' => now(),
                'status' => 'submitted',
                'score' => $max > 0 ? round(($score / $max) * 100, 2) : 0,
            ]);

            return $session->fresh('responses');
        });
    }

    public function logAntiCheat(int $sessionId, string $eventType, ?array $metadata = null): void
    {
        ExamAntiCheatLog::create([
            'session_id' => $sessionId,
            'event_type' => $eventType,
            'metadata' => $metadata,
            'logged_at' => now(),
        ]);
    }

    public function requestRemark(int $schoolId, array $data): RemarkRequest
    {
        return RemarkRequest::create(array_merge($data, ['school_id' => $schoolId]));
    }
}
