function relationObjectLabel(value: unknown): string | null {
  if (!value || typeof value !== 'object') return null

  const row = value as Record<string, unknown>
  const name =
    row.full_name
    ?? row.fullName
    ?? row.name
    ?? row.title
    ?? (`${row.first_name ?? ''} ${row.last_name ?? ''}`.trim() || null)

  if (name == null || String(name).trim() === '') return null

  const number = row.student_number ?? row.studentNumber
  if (number != null && String(number).trim() !== '') {
    return `${String(name)} · ${String(number)}`
  }

  return String(name)
}

function toCamelCase(value: string): string {
  return value.replace(/_([a-z])/g, (_, c: string) => c.toUpperCase())
}

export function relationLabelFromRow(row: Record<string, unknown>, key: string): string | null {
  if (!key.endsWith('_id')) return null

  const base = key.slice(0, -3)
  const camel = toCamelCase(base)
  const candidates = [
    base,
    camel,
    `${base}_model`,
    `${camel}Model`,
    `${base}_user`,
    `${camel}User`,
  ]

  for (const candidate of candidates) {
    const label = relationObjectLabel(row[candidate])
    if (label) return label
  }

  return null
}

export function enrichRelationLabelsDeep(value: unknown): unknown {
  if (Array.isArray(value)) {
    return value.map((item) => enrichRelationLabelsDeep(item))
  }

  if (!value || typeof value !== 'object') {
    return value
  }

  const source = value as Record<string, unknown>
  const next: Record<string, unknown> = {}

  for (const [key, raw] of Object.entries(source)) {
    next[key] = enrichRelationLabelsDeep(raw)

    if (!key.endsWith('_id')) continue
    const label = relationLabelFromRow(source, key)
    if (!label) continue

    const labelKey = `${key.slice(0, -3)}_label`
    if (next[labelKey] == null || next[labelKey] === '') {
      next[labelKey] = label
    }
  }

  return next
}
