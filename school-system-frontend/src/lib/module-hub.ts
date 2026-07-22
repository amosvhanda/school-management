import type { Component, InjectionKey } from 'vue'
import type { LocationQueryRaw, RouteLocationRaw } from 'vue-router'
import type { NavCapability } from '@/types/navigation'

export interface ModuleHubContext {
  embedded: true
}

export const moduleHubKey: InjectionKey<ModuleHubContext> = Symbol('moduleHub')

export interface ModuleHubSection {
  listKey: string
  title?: string
  description?: string
}

export interface ModuleHubTab {
  id: string
  title: string
  description: string
  icon: Component
  /** Capability required to see this tab (optional). */
  capability?: NavCapability | NavCapability[]
  /** Single registry CRUD section. */
  listKey?: string
  /** Multiple registry CRUD sections stacked in one tab. */
  sections?: ModuleHubSection[]
  /** Lazy view component for custom workspaces. */
  component?: () => Promise<Component | { default: Component }>
  /** Inline panel rendered by the hub (e.g. overview). */
  panel?: 'finance-overview' | 'people-overview' | 'operations-overview'
}

export function sectionsForHubTab(tab: ModuleHubTab | undefined): ModuleHubSection[] {
  if (!tab) return []
  if (tab.sections?.length) return tab.sections
  if (tab.listKey) return [{ listKey: tab.listKey }]
  return []
}

type RouteQuery = Record<string, unknown>

export function hubLocation(
  routeName: string,
  tabId: string,
  defaultTabId: string,
  options?: { create?: boolean; query?: RouteQuery },
): RouteLocationRaw {
  const query: LocationQueryRaw = { ...(options?.query as LocationQueryRaw) }

  if (tabId === defaultTabId) {
    delete query.tab
  } else {
    query.tab = tabId
  }

  if (options?.create) {
    query.create = '1'
  }

  return { name: routeName, query }
}

export function redirectToHubTab(
  routeName: string,
  tabId: string,
  defaultTabId = 'overview',
) {
  return (to: { query: RouteQuery }) =>
    hubLocation(routeName, tabId, defaultTabId, {
      create: typeof to.query.create === 'string' && to.query.create === '1',
      query: Object.fromEntries(
        Object.entries(to.query).filter(([key]) => key !== 'tab' && key !== 'create'),
      ),
    })
}
