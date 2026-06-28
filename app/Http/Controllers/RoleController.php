<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorizeRoleManagement(request());

        $permissionMap = $this->permissionMap();
        $roles = Role::withCount('users')->orderBy('name')->get();

        $formatted = $roles->map(function (Role $role) use ($permissionMap) {
            return $this->formatRole($role, $permissionMap);
        });

        return response()->json([
            'data' => $formatted,
        ]);
    }

    public function show($id)
    {
        $this->authorizeRoleManagement(request());

        $permissionMap = $this->permissionMap();
        $role = Role::withCount('users')->findOrFail($id);

        return response()->json([
            'data' => $this->formatRole($role, $permissionMap),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeRoleManagement($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permission_ids' => 'sometimes|array',
            'permission_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permissionMap = $this->permissionMap();
        $permissionIds = $this->normalizePermissionIds($request->input('permission_ids', []));

        $invalidPermissions = array_diff($permissionIds, array_keys($permissionMap));
        if (!empty($invalidPermissions)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'permission_ids' => ['One or more permissions are invalid.'],
                ],
            ], 422);
        }

        $slug = Str::slug($request->input('name'), '_');
        if (Role::where('slug', $slug)->exists()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'name' => ['A role with this name already exists.'],
                ],
            ], 422);
        }

        $role = Role::create([
            'name' => $request->input('name'),
            'slug' => $slug,
            'description' => $request->input('description'),
            'permission_ids' => $permissionIds,
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $this->formatRole($role->loadCount('users'), $permissionMap),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeRoleManagement($request);

        $role = Role::withCount('users')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'permission_ids' => 'sometimes|array',
            'permission_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permissionMap = $this->permissionMap();

        if ($request->filled('name')) {
            $slug = Str::slug($request->input('name'), '_');
            if (Role::where('slug', $slug)->where('id', '!=', $role->id)->exists()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        'name' => ['A role with this name already exists.'],
                    ],
                ], 422);
            }
            $role->name = $request->input('name');
            $role->slug = $slug;
        }

        if ($request->has('description')) {
            $role->description = $request->input('description');
        }

        if ($request->has('permission_ids')) {
            $permissionIds = $this->normalizePermissionIds($request->input('permission_ids', []));
            $invalidPermissions = array_diff($permissionIds, array_keys($permissionMap));
            if (!empty($invalidPermissions)) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        'permission_ids' => ['One or more permissions are invalid.'],
                    ],
                ], 422);
            }
            $role->permission_ids = $permissionIds;
        }

        $role->save();

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $this->formatRole($role->fresh()->loadCount('users'), $permissionMap),
        ]);
    }

    public function destroy($id)
    {
        $this->authorizeRoleManagement(request());

        $role = Role::withCount('users')->findOrFail($id);

        if ($this->isSystemRole($role->slug)) {
            return response()->json([
                'message' => 'System roles cannot be deleted.',
                'errors' => ['role' => ['This is a built-in role and cannot be removed.']],
            ], 422);
        }

        if (($role->users_count ?? 0) > 0) {
            return response()->json([
                'message' => 'Role is assigned to users.',
                'errors' => ['role' => ['Reassign users before deleting this role.']],
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }

    private function isSystemRole(string $slug): bool
    {
        return in_array($slug, [
            'admin',
            'teacher',
            'parent',
            'student',
            'finance',
            'accounts',
            'examination_officer',
        ], true);
    }

    private function permissionMap(): array
    {
        $map = [];
        foreach ($this->permissionCatalog() as $permission) {
            if (isset($permission['id'])) {
                $map[(int) $permission['id']] = $permission;
            }
        }

        return $map;
    }

    private function permissionCatalog(): array
    {
        return config('permissions', []);
    }

    private function normalizePermissionIds($permissionIds): array
    {
        if (!is_array($permissionIds)) {
            return [];
        }

        $normalized = array_map('intval', $permissionIds);
        $normalized = array_filter($normalized, fn ($value) => $value > 0);

        return array_values(array_unique($normalized));
    }

    private function formatRole(Role $role, array $permissionMap): array
    {
        $permissionIds = $this->normalizePermissionIds($role->permission_ids ?? []);
        $permissions = array_values(array_filter(array_map(
            fn ($id) => $permissionMap[$id] ?? null,
            $permissionIds
        )));

        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'permissions' => $permissions,
            'permission_ids' => $permissionIds,
            'user_count' => $role->users_count ?? 0,
            'is_system' => $this->isSystemRole($role->slug),
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at,
        ];
    }
}
