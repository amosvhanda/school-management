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

        if ($user->role === UserRole::Parent) {
            return ['dashboard.view'];
        }

        if ($user->role === UserRole::Student) {
            return ['dashboard.view'];
        }

        $dbRole = $this->roleForUser($user);
        if ($dbRole && is_array($dbRole->permission_ids) && $dbRole->permission_ids !== []) {
            return $this->slugsForPermissionIds($dbRole->permission_ids);
        }

        return $this->defaultSlugsForEnumRole(
            $user->role instanceof UserRole ? $user->role : UserRole::tryFromMixed($user->roleValue())
        );
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

        $dbRole = $this->roleForUser($user);
        if (! $dbRole || ! is_array($dbRole->permission_ids) || $dbRole->permission_ids === []) {
            $role = $user->role instanceof UserRole ? $user->role : UserRole::tryFromMixed($user->roleValue());

            return $role?->capabilities() ?? $base;
        }

        return $this->capabilitiesFromSlugs($this->permissionSlugsForUser($user));
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
     * @param  list<int>  $ids
     * @return list<string>
     */
    protected function slugsForPermissionIds(array $ids): array
    {
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
            UserRole::Teacher => [
                'reports.view', 'dashboard.view', 'students.manage', 'academics.manage',
                'attendance.manage', 'exams.enter_results', 'communications.manage',
            ],
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
