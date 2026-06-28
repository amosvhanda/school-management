export type ThemePreference = 'light' | 'dark'
export type ResolvedTheme = ThemePreference

const THEME_KEY = 'theme'

export function getSystemTheme(): ResolvedTheme {
  if (typeof window === 'undefined') return 'light'
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

/** Explicit user choice, or null when following the OS preference. */
export function getStoredThemePreference(): ThemePreference | null {
  if (typeof localStorage === 'undefined') return null
  const stored = localStorage.getItem(THEME_KEY)
  return stored === 'light' || stored === 'dark' ? stored : null
}

export function resolveTheme(preference: ThemePreference | null): ResolvedTheme {
  return preference ?? getSystemTheme()
}

export function applyTheme(theme: ResolvedTheme) {
  if (typeof document === 'undefined') return
  document.documentElement.classList.toggle('dark', theme === 'dark')
  document.documentElement.style.colorScheme = theme
}

export function setThemePreference(preference: ThemePreference) {
  localStorage.setItem(THEME_KEY, preference)
  applyTheme(preference)
}

let systemListenerAttached = false

export function watchSystemTheme(onChange: (theme: ResolvedTheme) => void) {
  if (typeof window === 'undefined') return () => {}

  const media = window.matchMedia('(prefers-color-scheme: dark)')
  const handler = () => {
    if (getStoredThemePreference() === null) {
      onChange(getSystemTheme())
    }
  }

  media.addEventListener('change', handler)
  return () => media.removeEventListener('change', handler)
}

/** Apply theme before Vue mounts and listen for OS changes when no manual preference is set. */
export function bootstrapTheme(onSystemChange?: (theme: ResolvedTheme) => void) {
  applyTheme(resolveTheme(getStoredThemePreference()))

  if (!systemListenerAttached) {
    systemListenerAttached = true
    watchSystemTheme((theme) => {
      applyTheme(theme)
      onSystemChange?.(theme)
    })
  }
}
