<?php

namespace App\Services\Platform;

use App\Models\School;

class BranchHierarchyService
{
    public function createBranch(School $parent, array $data): School
    {
        return School::create([
            'parent_school_id' => $parent->id,
            'branch_type' => $data['branch_type'] ?? 'branch',
            'name' => $data['name'],
            'code' => $data['code'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => 'active',
        ]);
    }

    public function tree(School $root): array
    {
        $branches = School::where('parent_school_id', $root->id)->get();

        return [
            'school' => $root->only(['id', 'name', 'code', 'branch_type', 'status']),
            'branches' => $branches->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'branch_type' => $b->branch_type,
                'status' => $b->status,
                'updated_at' => $b->updated_at?->toIso8601String(),
                'members_count' => Student::where('school_id', $b->id)->where('status', 'active')->count(),
                'children' => $this->tree($b)['branches'],
            ]),
        ];
    }
}
