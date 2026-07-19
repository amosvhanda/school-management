/**
 * Human-facing labels for related records.
 * Never expose numeric primary keys in the UI — use business identifiers / names.
 */

const EMPTY = '—'

function firstNonEmpty(...values: unknown[]): string | null {
  for (const value of values) {
    if (value == null) continue
    const text = String(value).trim()
    if (text !== '') return text
  }
  return null
}

/** True when a value looks like a raw database primary key. */
export function looksLikePrimaryKey(value: unknown): boolean {
  if (value == null || value === '') return false
  if (typeof value === 'number' && Number.isFinite(value)) return true
  return /^\d+$/.test(String(value).trim())
}

/**
 * Prefer name / code / business number. Never return a bare primary key.
 */
export function displayPerson(
  row: Record<string, unknown> | null | undefined,
  fallback = EMPTY,
): string {
  if (!row) return fallback
  return (
    firstNonEmpty(
      row.full_name,
      row.fullName,
      `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim(),
      row.name,
      row.student_number,
      row.employee_id,
      row.email,
    ) ?? fallback
  )
}

export function displayStudent(
  row: Record<string, unknown> | null | undefined,
  fallback = 'Student',
): string {
  if (!row) return fallback
  const name = firstNonEmpty(
    row.full_name,
    row.fullName,
    `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim(),
  )
  const number = firstNonEmpty(row.student_number)
  if (name && number) return `${name} · ${number}`
  return name ?? number ?? fallback
}

export function displaySchool(
  row: Record<string, unknown> | null | undefined,
  fallback = 'School',
): string {
  if (!row) return fallback
  return firstNonEmpty(row.name, row.code) ?? fallback
}

export function displayNamed(
  row: Record<string, unknown> | null | undefined,
  keys: string[],
  fallback = EMPTY,
): string {
  if (!row) return fallback
  return firstNonEmpty(...keys.map((key) => row[key])) ?? fallback
}

/**
 * Safe cell value: never render a raw primary key for `id` / `*_id` FK columns.
 * Non-numeric business codes (employee_id, client_id) are still shown.
 */
export function safeDisplayValue(key: string, value: unknown, relationLabel?: string | null): string {
  if (relationLabel) return relationLabel

  if (key === 'id') return EMPTY

  if (key.endsWith('_id')) {
    if (value == null || value === '') return EMPTY
    if (looksLikePrimaryKey(value)) return EMPTY
    return String(value)
  }

  if (value == null || value === '') return EMPTY
  return String(value)
}
