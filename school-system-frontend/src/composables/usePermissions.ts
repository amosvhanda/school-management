import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { canAccessNavItem } from '@/lib/permissions'
import type { NavGroup, NavItem } from '@/types/navigation'
import type { AuthUser } from '@/types/auth'

function filterNavItem(user: AuthUser | null, item: NavItem): NavItem | null {
  const children = (item.items ?? [])
    .map((child) => filterNavItem(user, child))
    .filter((child): child is NavItem => child != null)

  const selfOk = canAccessNavItem(user, item.capability, item.roles)

  if (children.length > 0) {
    // EduDash-style expander: show when any child is visible.
    if (!selfOk && item.capability) {
      // Parent gated and denied — only keep if children remain from mixed caps.
      // Still show container so mixed-capability modules (e.g. Attendance) work.
    }
    return {
      ...item,
      href: item.href ?? children[0]?.href,
      items: children,
    }
  }

  if (!selfOk) return null

  const { items: _items, ...rest } = item
  return rest
}

export function usePermissions() {
  const { user } = useAuth()

  function filterNavigation(groups: NavGroup[]) {
    return groups
      .map((group) => ({
        ...group,
        items: group.items
          .map((item) => filterNavItem(user.value, item))
          .filter((item): item is NavItem => item != null),
      }))
      .filter((group) => group.items.length > 0)
  }

  const isAdmin = computed(() => ['admin', 'super_admin'].includes(user.value?.role ?? ''))
  const isParent = computed(() => user.value?.role === 'parent')
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')

  return { filterNavigation, isAdmin, isParent, isSuperAdmin }
}
