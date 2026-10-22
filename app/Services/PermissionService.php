<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

class PermissionService
{
    /** @var array<string, bool>|null */
    protected ?array $capabilityKeys = null;

    public function catalog(): array
    {
        return config('permissions', []);
    }

    public function capabilityMap(): array
    {
        return config('permission_capabilities', []);
    }

    public function roleForUser(User $user): ?Role
    {
        return Role::query()->where('slug', $user->roleValue())->first();
    }

    /**
     * @return list<string>
     */
    public function permissionSlugsForUser(User $user): array
    {
        if ($user->role === UserRole::SuperAdmin) {
            return $this->allSlugs();
        }

        if ($user->role === UserRole::Parent || $user->role === UserRole::Student) {
            return ['dashboard.view'];
        }

        $roleSlugs = $this->roleSlugsForUser($user);
        $overrideSlugs = $this->slugsForPermissionIds(
            is_array($user->permission_ids) ? $user->permission_ids : []
        );

        return array_values(array_unique([...$roleSlugs, ...$overrideSlugs]));
    }

    /**
     * @return array<string, bool>
     */
    public function resolveCapabilities(User $user): array
    {
        $base = $this->emptyCapabilities();

        if ($user->role === UserRole::SuperAdmin) {
            return $this->allCapabilitiesTrue();
        }

        if ($user->role === UserRole::Parent) {
            return array_merge($base, ['isParent' => true]);
        }

        if ($user->role === UserRole::Student) {
            return $base;
        }

        $slugs = $this->permissionSlugsForUser($user);
        $fromSlugs = $this->capabilitiesFromSlugs($slugs);

        // If role has no DB permissions and no user overrides, fall back to enum defaults.
        $dbRole = $this->roleForUser($user);
        $hasRolePerms = $dbRole && is_array($dbRole->permission_ids) && $dbRole->permission_ids !== [];
        $hasOverrides = is_array($user->permission_ids) && $user->permission_ids !== [];

        if (! $hasRolePerms && ! $hasOverrides) {
            $role = $user->role instanceof UserRole ? $user->role : UserRole::tryFromMixed($user->roleValue());

            return $role?->capabilities() ?? $base;
        }

        return $fromSlugs;
    }

    public function hasCapability(User $user, string $capability): bool
    {
        return ($this->resolveCapabilities($user)[$capability] ?? false) === true;
    }

    public function hasPermission(User $user, string $slug): bool
    {
        return in_array($slug, $this->permissionSlugsForUser($user), true);
    }

    /**
     * @return list<array{id: int, name: string, slug: string, resource: string, action: string, description?: string, capabilities: list<string>}>
     */
    public function catalogWithRules(): array
    {
        $map = $this->capabilityMap();

        return array_map(function (array $permission) use ($map) {
            $slug = $permission['slug'] ?? '';

            return array_merge($permission, [
                'capabilities' => array_values($map[$slug] ?? []),
            ]);
        }, $this->catalog());
    }

    /**
     * @return list<string>
     */
    protected function roleSlugsForUser(User $user): array
    {
        $dbRole = $this->roleForUser($user);
        if ($dbRole && is_array($dbRole->permission_ids) && $dbRole->permission_ids !== []) {
            return $this->slugsForPermissionIds($dbRole->permission_ids);
        }

        return $this->defaultSlugsForEnumRole(
            $user->role instanceof UserRole ? $user->role : UserRole::tryFromMixed($user->roleValue())
        );
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<string>
     */
    protected function slugsForPermissionIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $idSet = array_flip(array_map('intval', $ids));
        $slugs = [];

        foreach ($this->catalog() as $permission) {
            $id = (int) ($permission['id'] ?? 0);
            if ($id > 0 && isset($idSet[$id])) {
                $slugs[] = (string) $permission['slug'];
            }
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    protected function allSlugs(): array
    {
        return array_values(array_filter(array_map(
            fn (array $permission) => $permission['slug'] ?? null,
            $this->catalog()
        )));
    }

    /**
     * @return list<string>
     */
    protected function defaultSlugsForEnumRole(?UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin, UserRole::SchoolAdmin => $this->allSlugs(),
            // Must mirror config/teacher_permissions.php and RoleSeeder.
            // Intentionally excludes 'academics.manage' — it maps to canManageTeachers.
            UserRole::Teacher => array_values(config('teacher_permissions.slugs', [
                'reports.view', 'dashboard.view', 'students.manage',
                'attendance.manage', 'exams.enter_results', 'communications.manage',
            ])),
            UserRole::Finance => [
                'reports.view', 'reports.generate', 'transactions.view', 'dashboard.view',
                'audit.view', 'finance.manage',
            ],
            UserRole::Accounts => [
                'reports.view', 'reports.generate', 'transactions.view', 'dashboard.view',
                'audit.view', 'finance.manage',
            ],
            UserRole::ExaminationOfficer => [
                'reports.view', 'reports.generate', 'dashboard.view', 'attendance.manage', 'exams.manage',
            ],
            UserRole::Receptionist => [
                'reports.view', 'dashboard.view', 'reception.manage',
            ],
            UserRole::Librarian => [
                'reports.view', 'dashboard.view', 'library.manage',
            ],
            UserRole::Nurse => [
                'reports.view', 'dashboard.view', 'health.manage', 'students.manage',
            ],
            UserRole::TransportManager => [
                'reports.view', 'dashboard.view', 'transport.manage',
            ],
            UserRole::HostelManager => [
                'reports.view', 'dashboard.view', 'hostel.manage',
            ],
            default => ['dashboard.view'],
        };
    }

    /**
     * @param  list<string>  $slugs
     * @return array<string, bool>
     */
    protected function capabilitiesFromSlugs(array $slugs): array
    {
        $capabilities = $this->emptyCapabilities();
        $map = $this->capabilityMap();

        foreach ($slugs as $slug) {
            foreach ($map[$slug] ?? [] as $capability) {
                if (array_key_exists($capability, $capabilities)) {
                    $capabilities[$capability] = true;
                }
            }
        }

        return $capabilities;
    }

    /**
     * @return array<string, bool>
     */
    protected function emptyCapabilities(): array
    {
        if ($this->capabilityKeys === null) {
            $this->capabilityKeys = array_fill_keys(array_keys(UserRole::Admin->capabilities()), false);
        }

        return $this->capabilityKeys;
    }

    /**
     * @return array<string, bool>
     */
    protected function allCapabilitiesTrue(): array
    {
        return array_fill_keys(array_keys($this->emptyCapabilities()), true);
    }
}
