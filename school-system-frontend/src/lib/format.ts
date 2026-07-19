/** School-facing locale: day-first dates familiar in Zimbabwe / Southern Africa. */
const SCHOOL_LOCALE = 'en-GB'
const SCHOOL_TIME_ZONE = 'Africa/Harare'
const FALLBACK = '—'

/**
 * Parse API / form date values.
 * - Date-only (YYYY-MM-DD): calendar date, no timezone shift
 * - ISO datetimes (with Z / offset / fractional seconds): absolute instant → format in Harare
 * - Naive local datetimes (YYYY-MM-DD HH:mm[:ss]): treated as wall-clock local
 */
export function parseDateValue(value: unknown): Date | null {
  if (value == null || value === '') return null
  if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value

  if (typeof value === 'number' && Number.isFinite(value)) {
    const fromNumber = new Date(value)
    return Number.isNaN(fromNumber.getTime()) ? null : fromNumber
  }

  const str = String(value).trim()
  if (!str) return null

  // Pure calendar date — keep as local calendar day (DOB, exam_date, leave dates)
  if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
    const [y, m, d] = str.split('-').map(Number)
    const date = new Date(y, m - 1, d)
    return Number.isNaN(date.getTime()) ? null : date
  }

  // Year-month
  if (/^\d{4}-\d{2}$/.test(str)) {
    const [y, m] = str.split('-').map(Number)
    const date = new Date(y, m - 1, 1)
    return Number.isNaN(date.getTime()) ? null : date
  }

  // Clock time only
  const timeOnly = str.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/)
  if (timeOnly) {
    const now = new Date()
    now.setHours(Number(timeOnly[1]), Number(timeOnly[2]), Number(timeOnly[3] ?? 0), 0)
    return now
  }

  // Absolute ISO timestamps from Laravel / JSON (Z, ±offset, fractional seconds)
  // e.g. 2026-07-19T12:30:00.000000Z or 2026-07-19T14:30:00+02:00
  if (
    /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/.test(str)
    && (/[zZ]$/.test(str) || /[+-]\d{2}:?\d{2}$/.test(str) || /\.\d+/.test(str) || str.includes('T'))
  ) {
    // Normalize Laravel microsecond timestamps: .000000Z → .000Z (JS Date accepts ms)
    const normalized = str
      .replace(' ', 'T')
      .replace(/\.(\d{3})\d+(?=[zZ]|[+-]|$)/, '.$1')
    const absolute = new Date(normalized)
    if (!Number.isNaN(absolute.getTime())) return absolute
  }

  // Naive datetime without timezone: treat as local wall clock
  const naive = str.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?/)
  if (naive) {
    const [, y, m, d, hh, mm, ss] = naive
    const date = new Date(
      Number(y),
      Number(m) - 1,
      Number(d),
      Number(hh),
      Number(mm),
      Number(ss ?? 0),
    )
    return Number.isNaN(date.getTime()) ? null : date
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
  const raw = String(value ?? '').trim()
  // Date-only strings must not be shifted by timezone conversion
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    const [y, m, d] = raw.split('-').map(Number)
    const calendar = new Date(y, m - 1, d)
    if (Number.isNaN(calendar.getTime())) return fallback
    return calendar.toLocaleDateString(SCHOOL_LOCALE, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  }

  const date = parseDateValue(value)
  if (!date) return fallback
  return date.toLocaleDateString(SCHOOL_LOCALE, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    timeZone: SCHOOL_TIME_ZONE,
  })
}

/**
 * Date and time in Africa/Harare — e.g. 19 Jul 2026 at 14:30
 * Calendar-only values omit the time part.
 */
export function formatDateTime(value: unknown, fallback = FALLBACK): string {
  const date = parseDateValue(value)
  if (!date) return fallback

  const raw = String(value ?? '').trim()
  const dateOnly = /^\d{4}-\d{2}-\d{2}$/.test(raw)
  if (dateOnly) return formatDate(date, fallback)

  const day = date.toLocaleDateString(SCHOOL_LOCALE, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    timeZone: SCHOOL_TIME_ZONE,
  })
  const time = date.toLocaleTimeString(SCHOOL_LOCALE, {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: SCHOOL_TIME_ZONE,
  })
  return `${day} at ${time}`
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
    const weekday = date.toLocaleDateString(SCHOOL_LOCALE, {
      weekday: 'long',
      timeZone: SCHOOL_TIME_ZONE,
    })
    return `${weekday} at ${formatTime(date)}`
  }
  return formatDateTime(date, fallback)
}

export function formatMonth(value: unknown, fallback = FALLBACK): string {
  const str = String(value ?? '').trim()
  if (!str) return fallback

  if (/^\d{4}-\d{2}$/.test(str)) {
    const date = parseDateValue(str)
    if (date) {
      return date.toLocaleDateString(SCHOOL_LOCALE, {
        month: 'short',
        year: 'numeric',
        timeZone: SCHOOL_TIME_ZONE,
      })
    }
  }

  return str
}

export function formatChartDay(value: unknown, fallback = ''): string {
  const raw = String(value ?? '').trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
    const [y, m, d] = raw.slice(0, 10).split('-').map(Number)
    const calendar = new Date(y, m - 1, d)
    if (Number.isNaN(calendar.getTime())) return fallback
    return calendar.toLocaleDateString(SCHOOL_LOCALE, { month: 'short', day: 'numeric' })
  }
  const date = parseDateValue(value)
  if (!date) return fallback
  return date.toLocaleDateString(SCHOOL_LOCALE, {
    month: 'short',
    day: 'numeric',
    timeZone: SCHOOL_TIME_ZONE,
  })
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
