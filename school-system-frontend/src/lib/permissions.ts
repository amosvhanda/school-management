import type { AuthUser, UserRole } from '@/types/auth'
import type { NavCapability } from '@/types/navigation'

const STAFF_ROLES: UserRole[] = [
  'admin',
  'teacher',
  'finance',
  'accounts',
  'examination_officer',
]

export function hasPermission(user: AuthUser | null, slug: string): boolean {
  if (!user) return false
  if (user.permissions?.includes(slug)) return true
  return false
}

export function hasCapability(user: AuthUser | null, capability: NavCapability): boolean {
  if (!user) return false

  if (user.capabilities && capability in user.capabilities) {
    return Boolean(user.capabilities[capability])
  }

  const role = user.role

  switch (capability) {
    case 'isSuperAdmin':
      return role === 'super_admin'
    case 'isParent':
      return role === 'parent'
    case 'isStaff':
      return STAFF_ROLES.includes(role) || role === 'super_admin'
    case 'canManageStudents':
      return ['super_admin', 'admin', 'teacher'].includes(role)
    case 'canManageTeachers':
      return ['super_admin', 'admin'].includes(role)
    case 'canManageFinance':
      return ['super_admin', 'admin', 'finance', 'accounts'].includes(role)
    case 'canViewAuditLogs':
      return ['super_admin', 'admin', 'finance', 'accounts'].includes(role)
    case 'canManageExaminations':
      return ['super_admin', 'admin', 'examination_officer'].includes(role)
    case 'canEnterExamResults':
      return ['super_admin', 'admin', 'teacher', 'examination_officer'].includes(role)
    case 'canManageLibrary':
    case 'canManageTransport':
    case 'canManageInventory':
    case 'canManageReception':
      return ['super_admin', 'admin'].includes(role)
    default:
      return false
  }
}

export function canAccessNavItem(
  user: AuthUser | null,
  capability?: NavCapability | NavCapability[],
  roles?: UserRole[],
): boolean {
  if (!user) return false
  if (roles?.length && !roles.includes(user.role)) return false

  if (!capability) return true

  const caps = Array.isArray(capability) ? capability : [capability]
  return caps.some((cap) => hasCapability(user, cap))
}

const STAFF_DASHBOARD_ROLES: UserRole[] = [
  'admin',
  'teacher',
  'finance',
  'accounts',
  'examination_officer',
]

export function isStaffDashboardRole(role: UserRole): boolean {
  return STAFF_DASHBOARD_ROLES.includes(role) || role === 'super_admin'
}

export function getDefaultRouteForRole(role: UserRole): string {
  switch (role) {
    case 'parent':
      return '/portal'
    case 'student':
      return '/student'
    case 'super_admin':
      return '/platform'
    default:
      return STAFF_DASHBOARD_ROLES.includes(role) ? '/' : '/login'
  }
}
