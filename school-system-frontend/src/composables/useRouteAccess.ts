import { useRouter, type RouteLocationRaw } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { canAccessRoute } from '@/lib/route-access'

/**
 * Route-aware access checks for in-page links.
 *
 * Uses the exact same `canAccessRoute` logic as the router guard, so a link is
 * shown only when navigating to it would actually be allowed. Prefer this (or
 * the `GuardedLink` component) over hardcoding capability checks next to every
 * `<RouterLink>` — it stays in sync with each route's `meta` and never drifts.
 */
export function useRouteAccess() {
  const { user } = useAuth()
  const router = useRouter()

  function canOpen(to: RouteLocationRaw): boolean {
    if (!user.value) return false
    try {
      return canAccessRoute(user.value, router.resolve(to))
    } catch {
      return false
    }
  }

  return { canOpen }
}
