<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $query = User::query();
        $currentUser = $request->user();

        if ($currentUser && ! $currentUser->isSuperAdmin() && $currentUser->school_id !== null) {
            $query->where('school_id', $currentUser->school_id);
        }

        if ($currentUser?->isSuperAdmin() && $request->filled('school_id')) {
            $query->where('school_id', $request->integer('school_id'));
        }

        if ($request->has('role')) {
            $roleParam = $request->input('role');
            $roles = is_array($roleParam)
                ? $roleParam
                : (is_string($roleParam) && strpos($roleParam, ',') !== false ? explode(',', $roleParam) : [$roleParam]);
            $query->whereIn('role', $roles);
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $users->map(fn (User $user) => $this->formatUser($user)),
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $this->findScopedUser($request, $id);

        return response()->json([
            'data' => $this->formatUser($user),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'firstName' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        [$firstName, $surname, $fullName] = $this->resolveNames($request);
        if ($fullName === null) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'name' => ['Name or first/surname is required.'],
                ],
            ], 422);
        }

        $role = $this->normalizeRole($request->input('role'));
        if ($role === null) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'role' => ['Invalid role selected.'],
                ],
            ], 422);
        }

        $currentUser = $request->user();
        $schoolId = $currentUser?->school_id;
        if ($currentUser && ! $currentUser->isSuperAdmin() && $schoolId === null) {
            return response()->json([
                'message' => 'User must be associated with a school to create users.',
                'errors' => [
                    'school_id' => ['User is not associated with a school.'],
                ],
            ], 422);
        }

        $password = $request->input('password') ?: 'password123';

        $user = User::create([
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $surname,
            'email' => $request->input('email'),
            'role' => $role,
            'status' => $request->input('status', 'active'),
            'password' => Hash::make($password),
            'school_id' => $currentUser?->isSuperAdmin() ? $request->input('school_id') : $schoolId,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $this->formatUser($user),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $this->findScopedUser($request, $id);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'firstName' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        [$firstName, $surname, $fullName] = $this->resolveNames($request, $user);
        if ($fullName !== null) {
            $user->name = $fullName;
        }
        if ($firstName !== null) {
            $user->first_name = $firstName;
        }
        if ($surname !== null) {
            $user->last_name = $surname;
        }

        if ($request->filled('email')) {
            $user->email = $request->input('email');
        }

        if ($request->has('role')) {
            $role = $this->normalizeRole($request->input('role'));
            if ($role === null) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        'role' => ['Invalid role selected.'],
                    ],
                ], 422);
            }
            $user->role = $role;
        }

        if ($request->has('status')) {
            $user->status = $request->input('status');
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $this->formatUser($user->fresh()),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();
        if ($currentUser && (int) $currentUser->id === (int) $id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user = $this->findScopedUser($request, $id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function activate(Request $request, $id)
    {
        $user = $this->findScopedUser($request, $id);
        $user->status = 'active';
        $user->save();

        return response()->json([
            'message' => 'User activated successfully',
            'data' => $this->formatUser($user),
        ]);
    }

    public function deactivate(Request $request, $id)
    {
        $user = $this->findScopedUser($request, $id);
        $user->status = 'inactive';
        $user->save();

        return response()->json([
            'message' => 'User deactivated successfully',
            'data' => $this->formatUser($user),
        ]);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = $this->findScopedUser($request, $id);
        $user->password = Hash::make('password123');
        $user->save();

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }

    public function assignRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = $this->normalizeRole($request->input('role'));
        if ($role === null) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'role' => ['Invalid role selected.'],
                ],
            ], 422);
        }

        $user = $this->findScopedUser($request, $id);
        $oldRole = $user->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user->role;
        $user->role = $role;
        $user->save();

        $this->auditService->log(
            module: 'administration',
            action: 'role_changed',
            auditable: $user,
            oldValues: ['role' => $oldRole],
            newValues: ['role' => $role],
            description: "User role changed from {$oldRole} to {$role}",
        );

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $this->formatUser($user),
        ]);
    }

    private function allowedRoles(): array
    {
        $roles = Role::pluck('slug')->filter()->values()->all();
        $roles = array_map('strtolower', $roles);
        $defaults = ['admin', 'teacher', 'parent', 'student', 'super_admin', 'school_admin'];

        return array_values(array_unique(array_merge($roles, $defaults)));
    }

    private function normalizeRole(?string $role): ?string
    {
        $role = $role ?: 'student';
        $role = strtolower(trim($role));
        if ($role === UserRole::SchoolAdmin->value) {
            return UserRole::SchoolAdmin->value;
        }
        $allowedRoles = $this->allowedRoles();

        return in_array($role, $allowedRoles, true) ? $role : null;
    }

    private function resolveNames(Request $request, ?User $existingUser = null): array
    {
        $firstName = $request->input('firstName') ?? $request->input('first_name');
        $surname = $request->input('surname') ?? $request->input('last_name');
        $name = $request->input('name');

        if (!$name) {
            $first = $firstName ?? $existingUser?->first_name;
            $last = $surname ?? $existingUser?->last_name;
            if ($first || $last) {
                $name = trim(trim((string) $first) . ' ' . trim((string) $last));
            }
        }

        if ((!$firstName || !$surname) && $name) {
            $parts = preg_split('/\s+/', trim($name));
            if ($parts) {
                if (!$firstName) {
                    $firstName = $parts[0] ?? null;
                }
                if (!$surname) {
                    $surname = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $surname;
                }
            }
        }

        if (!$name && $existingUser) {
            $name = $existingUser->name;
        }

        if (!$name) {
            return [null, null, null];
        }

        return [$firstName, $surname, $name];
    }

    private function formatUser(User $user): array
    {
        $firstName = $user->first_name ?? (explode(' ', $user->name)[0] ?? '');
        $surname = $user->last_name ?? (count(explode(' ', $user->name)) > 1 ? explode(' ', $user->name)[1] : '');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roleValue(),
            'status' => $user->status ?? 'active',
            'firstName' => $firstName,
            'surname' => $surname,
            'school_id' => $user->school_id,
            'lastLogin' => $user->updated_at ? $user->updated_at->format('Y-m-d H:i') : 'Never',
        ];
    }

    private function findScopedUser(Request $request, $id): User
    {
        $query = User::query();
        $currentUser = $request->user();

        if ($currentUser && ! $currentUser->isSuperAdmin() && $currentUser->school_id !== null) {
            $query->where('school_id', $currentUser->school_id);
        }

        return $query->findOrFail($id);
    }
}
