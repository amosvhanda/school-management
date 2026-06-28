import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

const STORAGE_KEY = 'dashboard_preferences'

interface DashboardPreferences {
  refreshIntervalMs: number | null
  collapsedSections: Record<string, boolean>
}

function loadPreferences(): DashboardPreferences {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return { refreshIntervalMs: null, collapsedSections: {} }
    }
    const parsed = JSON.parse(raw) as Partial<DashboardPreferences>
    return {
      refreshIntervalMs: parsed.refreshIntervalMs ?? null,
      collapsedSections: parsed.collapsedSections ?? {},
    }
  } catch {
    return { refreshIntervalMs: null, collapsedSections: {} }
  }
}

/** Persisted dashboard UI preferences (separate from TanStack Query server cache). */
export const useDashboardPreferencesStore = defineStore('dashboardPreferences', () => {
  const initial = loadPreferences()
  const refreshIntervalMs = ref<number | null>(initial.refreshIntervalMs)
  const collapsedSections = ref<Record<string, boolean>>(initial.collapsedSections)

  watch([refreshIntervalMs, collapsedSections], () => {
    const payload: DashboardPreferences = {
      refreshIntervalMs: refreshIntervalMs.value,
      collapsedSections: collapsedSections.value,
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload))
  }, { deep: true })

  function toggleSection(sectionId: string) {
    collapsedSections.value = {
      ...collapsedSections.value,
      [sectionId]: !collapsedSections.value[sectionId],
    }
  }

  function isSectionCollapsed(sectionId: string) {
    return Boolean(collapsedSections.value[sectionId])
  }

  function setRefreshInterval(ms: number | null) {
    refreshIntervalMs.value = ms
  }

  return {
    refreshIntervalMs,
    collapsedSections,
    toggleSection,
    isSectionCollapsed,
    setRefreshInterval,
  }
})
