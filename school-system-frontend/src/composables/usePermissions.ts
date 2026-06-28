import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { canAccessNavItem } from '@/lib/permissions'
import type { NavGroup } from '@/types/navigation'

export function usePermissions() {
  const { user } = useAuth()

  function filterNavigation(groups: NavGroup[]) {
    return groups
      .map((group) => ({
        ...group,
        items: group.items.filter((item) => canAccessNavItem(user.value, item.capability, item.roles)),
      }))
      .filter((group) => group.items.length > 0)
  }

  const isAdmin = computed(() => ['admin', 'super_admin'].includes(user.value?.role ?? ''))
  const isParent = computed(() => user.value?.role === 'parent')
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')

  return { filterNavigation, isAdmin, isParent, isSuperAdmin }
}
