import { computed, ref, watch } from 'vue'
import { useSchoolProfile } from '@/composables/useSchoolProfile'

const STORAGE_KEY = 'sms.academicYearFilter'

function nearbyYears(center?: string | number | null): string[] {
  const now = new Date().getFullYear()
  let base = now
  if (center != null) {
    const n = Number(String(center).match(/\d{4}/)?.[0])
    if (Number.isFinite(n) && n > 1900) base = n
  }
  return [`${base - 1} / ${base}`, `${base} / ${base + 1}`, `${base + 1} / ${base + 2}`]
}

function normalizeYearLabel(raw?: string | null): string | null {
  if (!raw) return null
  const text = String(raw).trim()
  if (!text) return null
  if (text.includes('/')) return text.replace(/\s*\/\s*/, ' / ')
  if (text.includes('-')) {
    const parts = text.split('-').map((p) => p.trim())
    if (parts.length >= 2) return `${parts[0]} / ${parts[1]}`
  }
  const n = Number(text)
  if (Number.isFinite(n) && n > 1900) return `${n} / ${n + 1}`
  return text
}

function yearStart(label: string): string {
  return String(label.match(/\d{4}/)?.[0] ?? new Date().getFullYear())
}

const selected = ref<string | null>(
  typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null,
)

export function useAcademicYearFilter() {
  const { school } = useSchoolProfile()

  const options = computed(() => {
    const fromSchool = normalizeYearLabel(school.value?.academic_year)
    const years = new Set(nearbyYears(fromSchool ?? school.value?.academic_year))
    if (fromSchool) years.add(fromSchool)
    if (selected.value) years.add(selected.value)
    return Array.from(years)
  })

  const academicYear = computed({
    get() {
      return selected.value
        ?? normalizeYearLabel(school.value?.academic_year)
        ?? options.value[1]
        ?? nearbyYears()[1]!
    },
    set(value: string) {
      selected.value = value
      try {
        localStorage.setItem(STORAGE_KEY, value)
      } catch {
        /* ignore */
      }
    },
  })

  const academicYearStart = computed(() => yearStart(academicYear.value))

  watch(
    () => school.value?.academic_year,
    (year) => {
      if (!selected.value && year) {
        const label = normalizeYearLabel(year)
        if (label) selected.value = label
      }
    },
    { immediate: true },
  )

  return {
    academicYear,
    academicYearStart,
    options,
    setAcademicYear(value: string) {
      academicYear.value = value
    },
  }
}
