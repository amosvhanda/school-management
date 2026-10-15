import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { canAccessNavItem, getDefaultRouteForRole, hasCapability } from '@/lib/permissions'
import type { NavCapability } from '@/types/navigation'
import type { UserRole } from '@/types/auth'

export function useAuth() {
  const authStore = useAuthStore()
  const { user, token, initialized, loading, isAuthenticated, role, defaultRoute } = storeToRefs(authStore)

  const displayName = computed(() => user.value?.name ?? user.value?.email ?? 'User')

  function checkCapability(capability: NavCapability) {
    return hasCapability(user.value, capability)
  }

  function canAccess(capability?: NavCapability | NavCapability[], roles?: UserRole[]) {
    return canAccessNavItem(user.value, capability, roles)
  }

  return {
    user,
    token,
    initialized,
    loading,
    isAuthenticated,
    role,
    defaultRoute,
    displayName,
    initialize: authStore.initialize,
    login: authStore.login,
    completeTwoFactorChallenge: authStore.completeTwoFactorChallenge,
    logout: authStore.logout,
    fetchMe: authStore.fetchMe,
    switchSchool: authStore.switchSchool,
    checkCapability,
    canAccess,
    getDefaultRouteForRole,
  }
}
