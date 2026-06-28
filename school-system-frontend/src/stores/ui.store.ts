import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import {
  SIDEBAR_COOKIE_MAX_AGE,
  SIDEBAR_COOKIE_NAME,
} from '@/components/ui/sidebar/utils'
import {
  applyTheme,
  bootstrapTheme,
  getStoredThemePreference,
  resolveTheme,
  setThemePreference,
  type ResolvedTheme,
  type ThemePreference,
} from '@/lib/theme'

const SIDEBAR_OPEN_KEY = 'sidebar_open'
const LEGACY_SIDEBAR_KEY = 'sidebar_collapsed'

function readSidebarOpen(): boolean {
  if (typeof document === 'undefined') return true

  const cookieMatch = document.cookie.match(/(?:^|;\s*)sidebar_state=([^;]+)/)
  if (cookieMatch) return cookieMatch[1] === 'true'

  const legacyCollapsed = localStorage.getItem(LEGACY_SIDEBAR_KEY)
  if (legacyCollapsed !== null) return legacyCollapsed !== 'true'

  const stored = localStorage.getItem(SIDEBAR_OPEN_KEY)
  if (stored !== null) return stored === 'true'

  return true
}

function persistSidebarOpen(value: boolean) {
  localStorage.setItem(SIDEBAR_OPEN_KEY, String(value))
  localStorage.removeItem(LEGACY_SIDEBAR_KEY)
  document.cookie = `${SIDEBAR_COOKIE_NAME}=${value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`
}

export const useUiStore = defineStore('ui', () => {
  const sidebarOpen = ref(readSidebarOpen())
  const preference = ref<ThemePreference | null>(getStoredThemePreference())
  const systemTheme = ref<ResolvedTheme>(resolveTheme(null))

  bootstrapTheme((theme) => {
    systemTheme.value = theme
  })

  const theme = computed<ResolvedTheme>(() => preference.value ?? systemTheme.value)

  watch(sidebarOpen, (value) => {
    persistSidebarOpen(value)
  })

  watch(theme, (value) => {
    applyTheme(value)
  }, { immediate: true })

  function setSidebarOpen(value: boolean) {
    sidebarOpen.value = value
  }

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function toggleTheme() {
    const next: ThemePreference = theme.value === 'light' ? 'dark' : 'light'
    preference.value = next
    setThemePreference(next)
  }

  return {
    sidebarOpen,
    theme,
    setSidebarOpen,
    toggleSidebar,
    toggleTheme,
  }
})
