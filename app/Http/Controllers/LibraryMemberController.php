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
            'member_id' => ['nullable', 'integer'],
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
            'member_id' => ['nullable', 'integer'],
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
