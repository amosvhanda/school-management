/** School-facing locale: day-first dates familiar in Zimbabwe / Southern Africa. */
const SCHOOL_LOCALE = 'en-GB'
const SCHOOL_TIME_ZONE = 'Africa/Harare'
const FALLBACK = '—'

export function parseDateValue(value: unknown): Date | null {
  if (value == null || value === '') return null
  if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value

  const str = String(value).trim()
  if (!str) return null

  const dateTimeMatch = str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?/)
  if (dateTimeMatch) {
    const [, y, m, d, hh, mm, ss] = dateTimeMatch
    if (hh != null) {
      return new Date(Number(y), Number(m) - 1, Number(d), Number(hh), Number(mm), Number(ss ?? 0))
    }
    return new Date(Number(y), Number(m) - 1, Number(d))
  }

  const monthMatch = str.match(/^(\d{4})-(\d{2})$/)
  if (monthMatch) {
    return new Date(Number(monthMatch[1]), Number(monthMatch[2]) - 1, 1)
  }

  const timeMatch = str.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/)
  if (timeMatch) {
    const now = new Date()
    now.setHours(Number(timeMatch[1]), Number(timeMatch[2]), Number(timeMatch[3] ?? 0), 0)
    return now
  }

  const parsed = new Date(str)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

export function isDateFieldKey(key: string): boolean {
  return key === 'date' || key.endsWith('_date') || key === 'date_of_birth'
}

export function isDateTimeFieldKey(key: string): boolean {
  return key.endsWith('_at') || key === 'check_in' || key === 'check_out' || key === 'timestamp'
}

export function isTimeFieldKey(key: string): boolean {
  return key.endsWith('_time')
}

/** Calendar date for school records — e.g. 19 Jul 2026 */
export function formatDate(value: unknown, fallback = FALLBACK): string {
  const date = parseDateValue(value)
  if (!date) return fallback
  return date.toLocaleDateString(SCHOOL_LOCALE, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

/** Date and time — e.g. 19 Jul 2026, 14:30 (Harare time for timestamps) */
export function formatDateTime(value: unknown, fallback = FALLBACK): string {
  const date = parseDateValue(value)
  if (!date) return fallback
  return date.toLocaleString(SCHOOL_LOCALE, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: SCHOOL_TIME_ZONE,
  })
}

/** Clock time — e.g. 14:30 */
export function formatTime(value: unknown, fallback = FALLBACK): string {
  const date = parseDateValue(value)
  if (!date) return fallback
  return date.toLocaleTimeString(SCHOOL_LOCALE, {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: SCHOOL_TIME_ZONE,
  })
}

export function formatRelativeTime(value: unknown, fallback = FALLBACK): string {
  const date = parseDateValue(value)
  if (!date) return fallback

  const diffMs = Date.now() - date.getTime()
  const diffMin = Math.floor(diffMs / 60_000)
  const diffHour = Math.floor(diffMin / 60)
  const diffDay = Math.floor(diffHour / 24)

  if (diffMin < 1) return 'Just now'
  if (diffMin < 60) return `${diffMin} minute${diffMin === 1 ? '' : 's'} ago`
  if (diffHour < 24) return `${diffHour} hour${diffHour === 1 ? '' : 's'} ago`
  if (diffDay === 1) return `Yesterday at ${formatTime(date)}`
  if (diffDay < 7) {
    return date.toLocaleString(SCHOOL_LOCALE, {
      weekday: 'long',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
      timeZone: SCHOOL_TIME_ZONE,
    })
  }
  return formatDateTime(date, fallback)
}

export function formatMonth(value: unknown, fallback = FALLBACK): string {
  const str = String(value ?? '').trim()
  if (!str) return fallback

  if (/^\d{4}-\d{2}$/.test(str)) {
    const date = parseDateValue(str)
    if (date) {
      return date.toLocaleDateString(SCHOOL_LOCALE, { month: 'short', year: 'numeric' })
    }
  }

  return str
}

export function formatChartDay(value: unknown, fallback = ''): string {
  const date = parseDateValue(value)
  if (!date) return fallback
  return date.toLocaleDateString(SCHOOL_LOCALE, { month: 'short', day: 'numeric' })
}

export function formatCellValue(key: string, value: unknown): string {
  if (value == null || value === '') return FALLBACK
  if (isDateTimeFieldKey(key)) return formatDateTime(value)
  if (isDateFieldKey(key)) return formatDate(value)
  if (isTimeFieldKey(key)) return formatTime(value)
  if (key === 'month' && /^\d{4}-\d{2}/.test(String(value))) return formatMonth(value)
  return String(value)
}

export function headerFromKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}
