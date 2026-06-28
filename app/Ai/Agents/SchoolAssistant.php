<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetSchoolStatsTool;
use App\Ai\Tools\SearchStudentsTool;
use App\Ai\Tools\SearchTeachersTool;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\TerminologyService;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class SchoolAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        public User $user,
        private TerminologyService $terminologyService,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $school = $this->user->school;
        $role = $this->user->role instanceof UserRole
            ? $this->user->role->value
            : (string) $this->user->role;

        $terminology = $school
            ? json_encode($this->terminologyService->getForSchool($school), JSON_THROW_ON_ERROR)
            : '{}';

        $schoolName = $school?->name ?? 'Unknown school';
        $academicYear = $school?->academic_year ?? 'not set';
        $currentTerm = $school?->current_term ?? 'not set';

        return <<<INSTRUCTIONS
You are a helpful school management assistant for "{$schoolName}".

The current user is {$this->user->name} (role: {$role}).
Academic year: {$academicYear}. Current term: {$currentTerm}.

School terminology overrides (use these labels when speaking to the user):
{$terminology}

Guidelines:
- Answer questions about students, teachers, attendance, and school operations using the available tools.
- Only discuss data for this school. Never invent student or staff records.
- If a tool returns no results, say so clearly and suggest refining the search.
- Keep answers concise and practical for school staff and administrators.
- Do not reveal passwords, API keys, or other sensitive credentials.
INSTRUCTIONS;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        $schoolId = $this->user->school_id;

        if (! $schoolId) {
            return [];
        }

        return [
            new SearchStudentsTool($schoolId),
            new SearchTeachersTool($schoolId),
            new GetSchoolStatsTool($schoolId),
        ];
    }
}
