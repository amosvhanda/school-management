import type { LocationQueryRaw, RouteLocationRaw } from 'vue-router'
import type { SetupTabId } from '@/modules/settings/school-setup-tabs'

type RouteQuery = Record<string, unknown>

/**
 * Named-route location for a School Setup tab (preferred for router redirects).
 */
export function schoolSetupLocation(
  tab: SetupTabId,
  options?: { create?: boolean; query?: RouteQuery },
): RouteLocationRaw {
  const query: LocationQueryRaw = { ...(options?.query as LocationQueryRaw) }

  if (tab === 'profile') {
    delete query.tab
  } else {
    query.tab = tab
  }

  if (options?.create) {
    query.create = '1'
  }

  return { name: 'settings', query }
}

/**
 * Href string for links / relation createRoute values.
 */
export function schoolSetupHref(
  tab: SetupTabId,
  options?: { create?: boolean },
): string {
  const params = new URLSearchParams()
  if (tab !== 'profile') params.set('tab', tab)
  if (options?.create) params.set('create', '1')
  const query = params.toString()
  return query ? `/settings?${query}` : '/settings'
}

/**
 * Vue Router redirect that preserves `?create=1` when moving into School Setup.
 */
export function redirectToSchoolSetup(tab: SetupTabId) {
  return (to: { query: RouteQuery }) =>
    schoolSetupLocation(tab, {
      create: typeof to.query.create === 'string' && to.query.create === '1',
    })
}
