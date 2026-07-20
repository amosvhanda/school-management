const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/

/** Normalize API / form values to `YYYY-MM-DD` for comparisons and min/max checks. */
export function isoDateFromValue(value?: string | null): string {
  if (!value) return ''
  const trimmed = String(value).trim().slice(0, 10)
  return ISO_DATE.test(trimmed) ? trimmed : ''
}

function pad2(n: number | string): string {
  return String(n).padStart(2, '0')
}

/** Parse typed dates: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY. Returns ISO or empty string. */
export function parseFlexibleDateInput(value: string): string {
  const trimmed = value.trim()
  if (!trimmed) return ''

  if (ISO_DATE.test(trimmed)) {
    return trimmed
  }

  const dmy = /^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/.exec(trimmed)
  if (dmy) {
    const day = Number(dmy[1])
    const month = Number(dmy[2])
    const year = Number(dmy[3])
    return `${year}-${pad2(month)}-${pad2(day)}`
  }

  return ''
}

export const CALENDAR_MONTHS = [
  { value: 0, label: 'January' },
  { value: 1, label: 'February' },
  { value: 2, label: 'March' },
  { value: 3, label: 'April' },
  { value: 4, label: 'May' },
  { value: 5, label: 'June' },
  { value: 6, label: 'July' },
  { value: 7, label: 'August' },
  { value: 8, label: 'September' },
  { value: 9, label: 'October' },
  { value: 10, label: 'November' },
  { value: 11, label: 'December' },
] as const

/** Years between min/max ISO dates (inclusive), newest first. */
export function yearsInRange(min?: string, max?: string): number[] {
  const now = new Date().getFullYear()
  const minYear = min ? Number.parseInt(min.slice(0, 4), 10) : now - 100
  const maxYear = max ? Number.parseInt(max.slice(0, 4), 10) : now + 10
  const from = Number.isFinite(minYear) ? minYear : now - 100
  const to = Number.isFinite(maxYear) ? maxYear : now + 10
  const years: number[] = []
  for (let year = to; year >= from; year -= 1) {
    years.push(year)
  }
  return years
}

export function isDateInRange(iso: string, min?: string, max?: string): boolean {
  const minIso = isoDateFromValue(min)
  const maxIso = isoDateFromValue(max)
  if (minIso && iso < minIso) return false
  if (maxIso && iso > maxIso) return false
  return true
}
