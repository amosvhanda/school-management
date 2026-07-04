import type { Router } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useNotificationStore } from '@/stores/notification.store'
import { getDefaultRouteForRole } from '@/lib/permissions'
import { canAccessRoute } from '@/lib/route-access'
import type { UserRole } from '@/types/auth'

export async function ensureAuthInitialized() {
  const authStore = useAuthStore()
  if (!authStore.initialized) {
    await authStore.bootstrapSession()
  }
  return authStore
}

export function createRouteGuards(router: Router) {
  router.beforeEach(async (to) => {
    const authStore = await ensureAuthInitialized()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }

    if (to.meta.guest && authStore.isAuthenticated) {
      return authStore.defaultRoute
    }

    if (to.meta.requiresAuth && authStore.user) {
      if (!canAccessRoute(authStore.user, to)) {
        useNotificationStore().notify({
          title: 'Access denied',
          description: 'You do not have permission to view this page.',
          variant: 'destructive',
        })
        return authStore.defaultRoute
      }

      if (to.path === '/' && authStore.user.role === 'parent') {
        return '/portal'
      }
      if (to.path === '/' && authStore.user.role === 'student') {
        return '/student'
      }
      if (to.path === '/' && authStore.user.role === 'super_admin') {
        return '/platform'
      }
    }

    return true
  })
}

export function redirectAfterLogin(role: UserRole) {
  return getDefaultRouteForRole(role)
}

export { resolvePostLoginRedirect } from '@/lib/route-access'
