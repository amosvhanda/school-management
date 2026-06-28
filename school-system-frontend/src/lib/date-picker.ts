const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/

/** Normalize API / form values to `YYYY-MM-DD` for comparisons and min/max checks. */
export function isoDateFromValue(value?: string | null): string {
  if (!value) return ''
  const trimmed = String(value).trim().slice(0, 10)
  return ISO_DATE.test(trimmed) ? trimmed : ''
}
