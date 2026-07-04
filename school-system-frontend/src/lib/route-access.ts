import type { RouteLocationNormalized } from 'vue-router'
import type { AuthUser, UserRole } from '@/types/auth'
import type { NavCapability } from '@/types/navigation'
import { getDefaultRouteForRole, hasCapability, isStaffDashboardRole } from './permissions'

const PARENT_PREFIX = '/portal'
const STUDENT_PREFIX = '/student'
const PLATFORM_PREFIX = '/platform'

export function isParentPath(path: string): boolean {
  return path === PARENT_PREFIX || path.startsWith(`${PARENT_PREFIX}/`)
}

export function isStudentPath(path: string): boolean {
  return path === STUDENT_PREFIX || path.startsWith(`${STUDENT_PREFIX}/`)
}

export function isPlatformPath(path: string): boolean {
  return path === PLATFORM_PREFIX || path.startsWith(`${PLATFORM_PREFIX}/`)
}

function checkMetaCapabilities(user: AuthUser, capability?: NavCapability | NavCapability[]): boolean {
  if (!capability) return true
  const caps = Array.isArray(capability) ? capability : [capability]
  return caps.some((cap) => hasCapability(user, cap))
}

function checkMetaRoles(user: AuthUser, roles?: UserRole[]): boolean {
  if (!roles?.length) return true
  return roles.includes(user.role)
}

/**
 * Returns whether the signed-in user may navigate to the given route.
 * Used by route guards and post-login redirect validation.
 */
export function canAccessRoute(
  user: AuthUser,
  to: Pick<RouteLocationNormalized, 'path' | 'meta'> & { name?: RouteLocationNormalized['name'] | null },
): boolean {
  if (to.name === 'not-found') return false

  const path = to.path
  const licensePath = path === '/license/activate'

  if (user.role === 'student') {
    if (licensePath) return true
    return isStudentPath(path)
  }

  if (user.role === 'parent') {
    if (licensePath) return true
    return isParentPath(path)
  }

  if (user.role === 'super_admin') {
    if (isParentPath(path) || isStudentPath(path)) return false
    if (isPlatformPath(path)) return true
    if (path === '/') return false
  } else if (isStaffDashboardRole(user.role)) {
    if (isParentPath(path) || isStudentPath(path) || isPlatformPath(path)) return false
  } else {
    return false
  }

  if (!checkMetaRoles(user, to.meta.roles as UserRole[] | undefined)) {
    return false
  }

  if (!checkMetaCapabilities(user, to.meta.capability as NavCapability | NavCapability[] | undefined)) {
    return false
  }

  if (path === '/' && user.role === 'super_admin') {
    return false
  }

  return true
}

/** Pick a safe redirect after login — never sends users to routes they cannot access. */
export function resolvePostLoginRedirect(
  user: AuthUser,
  requestedPath: string | null | undefined,
  resolve: (path: string) => Pick<RouteLocationNormalized, 'path' | 'meta'> & { name?: RouteLocationNormalized['name'] | null },
): string {
  if (
    requestedPath
    && requestedPath !== '/login'
    && !requestedPath.startsWith('/login?')
  ) {
    const target = resolve(requestedPath)
    if (canAccessRoute(user, target)) {
      return requestedPath
    }
  }

  return getDefaultRouteForRole(user.role)
}
