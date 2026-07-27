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
  /** Optional group label for sidebar / grouped navigation. */
  group?: string
  /**
   * Sidebar weight for dense hubs (e.g. Teaching).
   * Secondary items sit under a collapsed "More" section.
   */
  priority?: 'primary' | 'secondary'
  /** Capability required to see this tab (optional). */
  capability?: NavCapability | NavCapability[]
  /**
   * When true, hide this tab for accounts without a linked teacher profile
   * (school admins still keep school-wide LMS).
   */
  requiresTeacherProfile?: boolean
  /** Single registry CRUD section. */
  listKey?: string
  /** Multiple registry CRUD sections stacked in one tab. */
  sections?: ModuleHubSection[]
  /** Lazy view component for custom workspaces. */
  component?: () => Promise<Component | { default: Component }>
  /** Inline panel rendered by the hub (e.g. overview). */
  panel?: 'finance-overview' | 'people-overview' | 'operations-overview'
}

export interface ModuleHubTabGroup {
  label: string
  tabs: ModuleHubTab[]
}

/** Preserve first-seen group order; ungrouped tabs fall under a blank label. */
export function groupModuleHubTabs(tabs: ModuleHubTab[]): ModuleHubTabGroup[] {
  const groups: ModuleHubTabGroup[] = []
  const indexByLabel = new Map<string, number>()

  for (const tab of tabs) {
    const label = tab.group?.trim() || ''
    const existing = indexByLabel.get(label)
    if (existing === undefined) {
      indexByLabel.set(label, groups.length)
      groups.push({ label, tabs: [tab] })
    } else {
      groups[existing].tabs.push(tab)
    }
  }

  return groups
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
  extraQuery?: RouteQuery,
) {
  return (to: { query: RouteQuery }) =>
    hubLocation(routeName, tabId, defaultTabId, {
      create: typeof to.query.create === 'string' && to.query.create === '1',
      query: {
        ...Object.fromEntries(
          Object.entries(to.query).filter(([key]) => key !== 'tab' && key !== 'create'),
        ),
        ...extraQuery,
      },
    })
}

/** Whether a hub/setup section should open its create sheet from ?create=1[&section=listKey]. */
export function shouldAutoCreateSection(
  section: { listKey: string },
  index: number,
  query: { create?: unknown; section?: unknown },
): boolean {
  const create = Array.isArray(query.create) ? query.create[0] : query.create
  if (create !== '1') return false
  const target = String(Array.isArray(query.section) ? query.section[0] : query.section ?? '')
  if (target) return section.listKey === target
  return index === 0
}
