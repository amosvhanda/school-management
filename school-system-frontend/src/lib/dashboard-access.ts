import type { Router } from 'vue-router'
import type { AuthUser, UserRole } from '@/types/auth'
import type { NavCapability } from '@/types/navigation'
import { canAccessNavItem } from '@/lib/permissions'
import { canAccessRoute } from '@/lib/route-access'

/**
 * Staff dashboard rule: if the user cannot open it, it must not appear.
 * - Capability (when set) must pass
 * - Staff items without capability are hidden (use skip/allowWithoutCapability for portals)
 * - Href is resolved and checked with the same route guard rules
 */
export function canShowDashboardItem(
  user: AuthUser | null,
  options: {
    href?: string
    capability?: NavCapability | NavCapability[]
    roles?: UserRole[]
    /** Portal / role-scoped lists that omit capability on purpose */
    allowWithoutCapability?: boolean
  },
  router?: Pick<Router, 'resolve'>,
): boolean {
  if (!user) return false

  if (options.roles?.length && !options.roles.includes(user.role)) {
    return false
  }

  if (options.capability != null) {
    if (!canAccessNavItem(user, options.capability, options.roles)) {
      return false
    }
  } else if (!options.allowWithoutCapability) {
    return false
  }

  if (options.href && router) {
    try {
      const resolved = router.resolve(options.href)
      if (!canAccessRoute(user, resolved)) {
        return false
      }
    } catch {
      return false
    }
  }

  return true
}
