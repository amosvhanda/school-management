<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\LibraryMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LibraryMemberController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return LibraryMember::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageLibrary'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['library.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'Library member';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'member_type' => ['required', 'in:student,teacher,employee,external'],
            'member_id' => $this->memberIdRules($request, $schoolId),
            'member_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('library_members')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'joined_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'member_type' => ['sometimes', 'in:student,teacher,employee,external'],
            'member_id' => $this->memberIdRules($request, $schoolId, updating: true),
            'member_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('library_members')->where(fn ($q) => $q->where('school_id', $schoolId))->ignore($id),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'joined_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return list<mixed>
     */
    protected function memberIdRules(Request $request, int $schoolId, bool $updating = false): array
    {
        $type = $request->input('member_type');
        if ($updating && ! $request->filled('member_type') && $request->route('id')) {
            $existing = LibraryMember::query()
                ->where('school_id', $schoolId)
                ->find($request->route('id'));
            $type = $existing?->member_type;
        }

        $table = match ($type) {
            'student' => 'students',
            'teacher' => 'teachers',
            'employee' => 'employees',
            default => null,
        };

        if ($table === null) {
            return [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && $value !== '') {
                        $fail('External members cannot link to a school record.');
                    }
                },
            ];
        }

        return [
            'required',
            'integer',
            Rule::exists($table, 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
        ];
    }

    protected function applyIndexFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('member_type')) {
            $query->where('member_type', $request->string('member_type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $query;
    }
}
