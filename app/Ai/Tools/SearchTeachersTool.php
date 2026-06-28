<?php

namespace App\Ai\Tools;

use App\Models\Teacher;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchTeachersTool implements Tool
{
    public function __construct(private int $schoolId) {}

    public function description(): Stringable|string
    {
        return 'Search teachers in the current school by name, employee number, or email.';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) $request->string('query'));

        $query = Teacher::query()
            ->where('school_id', $this->schoolId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $teachers = $query
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'employee_id', 'name', 'first_name', 'last_name', 'department', 'status', 'email', 'phone']);

        if ($teachers->isEmpty()) {
            return 'No teachers matched the search criteria.';
        }

        return "Found {$teachers->count()} teacher(s):\n\n".$teachers->toJson(JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Name, employee number, or email fragment to search for.')
                ->required(),
            'status' => $schema->string()
                ->description('Optional status filter, e.g. active or inactive.'),
        ];
    }
}
