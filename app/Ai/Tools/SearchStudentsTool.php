<?php

namespace App\Ai\Tools;

use App\Models\Student;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchStudentsTool implements Tool
{
    public function __construct(private int $schoolId) {}

    public function description(): Stringable|string
    {
        return 'Search students in the current school by name, student number, class, or email.';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) $request->string('query'));

        $query = Student::query()
            ->where('school_id', $this->schoolId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('class'), fn ($q) => $q->where('class', $request->string('class')));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query
            ->orderBy('full_name')
            ->limit(15)
            ->get(['id', 'student_number', 'full_name', 'class', 'status', 'balance', 'currency', 'email']);

        if ($students->isEmpty()) {
            return 'No students matched the search criteria.';
        }

        return "Found {$students->count()} student(s):\n\n".$students->toJson(JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Name, student number, class, or email fragment to search for.')
                ->required(),
            'status' => $schema->string()
                ->description('Optional status filter, e.g. active or inactive.'),
            'class' => $schema->string()
                ->description('Optional class name filter.'),
        ];
    }
}
