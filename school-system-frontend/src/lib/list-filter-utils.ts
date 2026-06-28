import type { ListFilterSchema } from '@/modules/shared/list-filters'

export function resolveRowFilterValue(
  row: Record<string, unknown>,
  key: string,
): string {
  if (key === 'class_id') {
    const classModel = row.class_model ?? row.classModel
    if (classModel && typeof classModel === 'object') {
      return String((classModel as Record<string, unknown>).id ?? '')
    }
    return String(row.class_id ?? '')
  }
  if (key === 'grade_level_id') {
    const grade = row.grade_level ?? row.gradeLevel
    if (grade && typeof grade === 'object') {
      return String((grade as Record<string, unknown>).id ?? '')
    }
    return String(row.grade_level_id ?? '')
  }
  if (key === 'guardian_id') {
    const guardians = row.guardians
    if (Array.isArray(guardians) && guardians.length) {
      return guardians.map((g) => String((g as Record<string, unknown>).id)).join(',')
    }
    const guardian = row.guardian
    if (guardian && typeof guardian === 'object') {
      return String((guardian as Record<string, unknown>).id ?? '')
    }
    return String(row.guardian_id ?? '')
  }

  return String(row[key] ?? '')
}

/** Flatten common row shapes into searchable text for client-side table search. */
export function flattenRowSearchText(row: Record<string, unknown>): string {
  const parts: string[] = []

  function append(value: unknown) {
    if (value == null || value === '') return
    if (typeof value === 'object') return
    parts.push(String(value))
  }

  for (const [key, value] of Object.entries(row)) {
    if (key === 'class_model' || key === 'classModel') {
      const model = value as Record<string, unknown> | null
      append(model?.name)
      append(model?.form)
      continue
    }
    if (key === 'grade_level' || key === 'gradeLevel') {
      const model = value as Record<string, unknown> | null
      append(model?.name)
      continue
    }
    if (key === 'guardians' && Array.isArray(value)) {
      for (const guardian of value) {
        if (guardian && typeof guardian === 'object') {
          const g = guardian as Record<string, unknown>
          append(g.first_name)
          append(g.last_name)
          append(g.phone)
          append(g.email)
        }
      }
      continue
    }
    if (key === 'guardian' && value && typeof value === 'object') {
      const g = value as Record<string, unknown>
      append(g.first_name)
      append(g.last_name)
      append(g.phone)
      append(g.email)
      continue
    }
    if (key === 'student' && value && typeof value === 'object') {
      const s = value as Record<string, unknown>
      append(s.full_name)
      append(s.first_name)
      append(s.last_name)
      append(s.student_number)
      continue
    }
    append(value)
  }

  return parts.join(' ').toLowerCase()
}

export function applyClientFilters(
  rows: Record<string, unknown>[],
  filters: ListFilterSchema[],
  values: Record<string, string>,
): Record<string, unknown>[] {
  const active = filters.filter((f) => values[f.key])
  if (!active.length) return rows

  return rows.filter((row) =>
    active.every((filter) => {
      const expected = values[filter.key]
      if (!expected) return true

      if (filter.key === 'guardian_id') {
        const guardians = row.guardians
        if (Array.isArray(guardians)) {
          return guardians.some((g) => String((g as Record<string, unknown>).id) === expected)
        }
        return String(row.guardian_id ?? '') === expected
      }

      return resolveRowFilterValue(row, filter.key) === expected
    }),
  )
}
