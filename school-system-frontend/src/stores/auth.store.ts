import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getStoredToken, setStoredToken } from '@/lib/api'
import { queryClient } from '@/lib/query-client'
import { getDefaultRouteForRole, hasCapability, hasPermission as checkPermission } from '@/lib/permissions'
import {
  fetchCurrentUser,
  login as loginApi,
  logout as logoutApi,
} from '@/services/auth.service'
import { useConfigStore } from '@/stores/config.store'
import { useNotificationStore } from '@/stores/notification.store'
import type { AuthUser } from '@/types/auth'
import type { NavCapability } from '@/types/navigation'

const roleNames: Record<string, string> = {
  super_admin: 'Super admin',
  admin: 'Admin',
  teacher: 'Teacher',
  parent: 'Parent',
  student: 'Student',
  finance: 'Finance',
  accounts: 'Accounts',
  examination_officer: 'Exam officer',
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const token = ref<string | null>(getStoredToken())
  const initialized = ref(false)
  const loading = ref(false)
  let bootstrapPromise: Promise<void> | null = null

  const isAuthenticated = computed(() => Boolean(token.value))
  const role = computed(() => user.value?.role ?? null)

  const roleName = computed(() => {
    if (!role.value) return null
    return roleNames[role.value] ?? role.value
  })

  const defaultRoute = computed(() =>
    user.value ? getDefaultRouteForRole(user.value.role) : '/',
  )

  /** Permission slugs from the API (custom roles included). */
  const permissions = computed(() => user.value?.permissions ?? [])

  /** Capability flags from the API (custom roles included). */
  const capabilities = computed(() => user.value?.capabilities ?? null)

  function hasPermission(permission: string) {
    return checkPermission(user.value, permission)
  }

  function checkCapability(capability: NavCapability) {
    return hasCapability(user.value, capability)
  }

  async function bootstrapSession() {
    if (initialized.value && !loading.value) return

    if (bootstrapPromise) {
      await bootstrapPromise
      return
    }

    bootstrapPromise = (async () => {
      if (!token.value) {
        user.value = null
        initialized.value = true
        return
      }

      loading.value = true

      try {
        if (!user.value) {
          await fetchMe()
        }
      } catch {
        await logout()
        return
      }

      try {
        const configStore = useConfigStore()
        if (!configStore.loaded) {
          if (user.value?.school_id != null) {
            await configStore.fetchPublicConfig()
          } else {
            configStore.markLoadedWithoutSchool()
          }
        }
      } finally {
        loading.value = false
        initialized.value = true
      }
    })()

    try {
      await bootstrapPromise
    } finally {
      bootstrapPromise = null
    }
  }

  async function initialize() {
    await bootstrapSession()
  }

  async function login(email: string, password: string, roleFilter?: string) {
    loading.value = true

    try {
      const payload = await loginApi(email, password, roleFilter)

      token.value = payload.token
      user.value = payload.user
      useNotificationStore().reset()

      setStoredToken(payload.token)

      const configStore = useConfigStore()
      if (!configStore.loaded) {
        if (payload.user.school_id != null) {
          await configStore.fetchPublicConfig()
        } else {
          configStore.markLoadedWithoutSchool()
        }
      }

      initialized.value = true
      return payload
    } finally {
      loading.value = false
    }
  }

  async function fetchMe() {
    const nextUser = await fetchCurrentUser()
    user.value = nextUser
    return nextUser
  }

  async function logout() {
    try {
      if (token.value) await logoutApi()
    } finally {
      user.value = null
      token.value = null
      setStoredToken(null)
      initialized.value = true
      bootstrapPromise = null
      queryClient.clear()
      useConfigStore().reset()
      useNotificationStore().reset()
    }
  }

  function setUser(next: AuthUser | null) {
    user.value = next
  }

  return {
    user,
    token,
    initialized,
    loading,

    isAuthenticated,
    role,
    roleName,
    permissions,
    capabilities,
    hasPermission,
    checkCapability,

    defaultRoute,

    initialize,
    bootstrapSession,
    login,
    fetchMe,
    logout,
    setUser,
  }
})
