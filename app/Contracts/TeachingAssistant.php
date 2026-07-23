<?php

namespace App\Contracts;

interface TeachingAssistant
{
    /**
     * Generate teaching content for a given task.
     *
     * Supported tasks: lesson_plan, quiz, exam_paper, marking_guide,
     * teaching_activities, class_analysis, interventions, student_comment,
     * progress_summary.
     *
     * @param  array<string, mixed>  $context
     * @return array{task: string, title: string, content: string, meta: array<string, mixed>}
     */
    public function generate(string $task, array $context = []): array;

    /**
     * Whether a real AI provider is wired (false for the stub driver).
     */
    public function isLive(): bool;
}
