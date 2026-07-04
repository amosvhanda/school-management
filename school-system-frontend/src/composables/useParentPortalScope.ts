import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'

type ChildLike = { id: number | string }

export function useParentPortalScope(namespace: string) {
  const { user } = useAuth()

  const storageKey = computed(() => {
    const userId = user.value?.id
    return userId ? `parent-portal-scope:${namespace}:${userId}` : null
  })

  function read(fallback = ''): string {
    const key = storageKey.value
    if (!key || typeof localStorage === 'undefined') return fallback
    try {
      const value = localStorage.getItem(key)
      return value && value.trim() ? value : fallback
    } catch {
      return fallback
    }
  }

  function write(value: string) {
    const key = storageKey.value
    if (!key || typeof localStorage === 'undefined') return
    try {
      if (value && value.trim()) {
        localStorage.setItem(key, value)
        return
      }
      localStorage.removeItem(key)
    } catch {
      // Ignore storage failures in private mode / restricted environments.
    }
  }

  function resolveChildSelection(children: ChildLike[], preferred: string, emptyFallback = ''): string {
    if (!children.length) return emptyFallback
    if (preferred && children.some((child) => String(child.id) === preferred)) {
      return preferred
    }
    return String(children[0].id)
  }

  return {
    storageKey,
    read,
    write,
    resolveChildSelection,
  }
}
