import { fetchList } from '@/services/dashboard.service'
import { moduleEndpoints } from '@/services'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'

export interface RelationOption {
  value: string
  label: string
  raw: Record<string, unknown>
}

function cacheKey(endpoint: string, params?: Record<string, unknown>): string {
  return `${endpoint}::${JSON.stringify(params ?? {})}`
}

const optionCache = new Map<string, RelationOption[]>()

export async function loadRelationOptions(
  endpoint: string,
  params?: Record<string, unknown>,
  forceRefresh = false,
): Promise<RelationOption[]> {
  const key = cacheKey(endpoint, params)
  if (!forceRefresh && optionCache.has(key)) return optionCache.get(key)!

  const rows = await fetchList<Record<string, unknown>>(endpoint, {
    all: true,
    limit: 500,
    ...params,
  })

  const options = rows.map((row) => ({
    value: String(row.id ?? ''),
    label: formatRelationLabel(row, endpoint),
    raw: row,
  }))

  optionCache.set(key, options)
  return options
}

function formatRelationLabel(row: Record<string, unknown>, endpoint: string): string {
  if (endpoint.includes('/students')) {
    const name = String(
      row.full_name
      ?? (`${row.first_name ?? ''} ${row.last_name ?? ''}`.trim() || `Student #${row.id}`),
    )
    const number = row.student_number ? ` · ${row.student_number}` : ''
    return `${name}${number}`
  }
  if (endpoint.includes('/guardians')) {
    const name = `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim()
    const phone = row.phone ? ` · ${row.phone}` : ''
    return `${name || 'Guardian'}${phone}`
  }
  if (endpoint.includes('/classes')) {
    const name = String(row.name ?? 'Class')
    const form = row.form ? ` · ${row.form}` : ''
    return `${name}${form}`
  }
  if (endpoint.includes('/teachers')) {
    return String(
      row.name
      ?? row.full_name
      ?? (`${row.first_name ?? ''} ${row.last_name ?? ''}`.trim() || `Teacher #${row.id}`),
    )
  }
  if (endpoint.includes('/grade-levels')) {
    return String(row.name ?? `Grade ${row.id}`)
  }
  if (endpoint.includes('/subjects')) {
    const code = row.code ? ` (${row.code})` : ''
    return `${row.name ?? 'Subject'}${code}`
  }
  if (endpoint.includes('/departments')) {
    return String(row.name ?? `Department ${row.id}`)
  }
  if (endpoint.includes('/inventory')) {
    const sku = row.sku ? ` · ${row.sku}` : ''
    return `${row.name ?? 'Item'}${sku}`
  }
  if (endpoint.includes('/transport/vehicles')) {
    const reg = row.registration_number ? ` · ${row.registration_number}` : ''
    return `${row.make ?? 'Vehicle'}${reg}`
  }
  if (endpoint.includes('/transport/drivers')) {
    const license = row.license_number ? ` · ${row.license_number}` : ''
    return `${row.full_name ?? 'Driver'}${license}`
  }
  if (endpoint.includes('/payroll/teachers')) {
    return String(
      row.name
      ?? row.full_name
      ?? (`${row.first_name ?? ''} ${row.last_name ?? ''}`.trim() || `Staff #${row.id}`),
    )
  }
  if (endpoint.includes('/invoices')) {
    const number = row.invoice_number ? String(row.invoice_number) : `#${row.id}`
    const balance = row.balance != null ? ` · bal ${row.balance}` : ''
    const desc = row.description ? ` — ${String(row.description).slice(0, 40)}` : ''
    return `${number}${balance}${desc}`
  }
  return String(row.name ?? row.title ?? row.full_name ?? row.label ?? row.id ?? '—')
}

export function getCachedRelationOptions(
  endpoint: string,
  params?: Record<string, unknown>,
): RelationOption[] {
  return optionCache.get(cacheKey(endpoint, params)) ?? []
}

export function findRelationLabel(
  endpoint: string,
  id: unknown,
  params?: Record<string, unknown>,
): string | undefined {
  return getCachedRelationOptions(endpoint, params).find((o) => o.value === String(id))?.label
}

export function findRelationRaw(
  endpoint: string,
  id: unknown,
  params?: Record<string, unknown>,
): Record<string, unknown> | undefined {
  return getCachedRelationOptions(endpoint, params).find((o) => o.value === String(id))?.raw
}

export function findRelationIdByLabel(
  endpoint: string,
  label: unknown,
  params?: Record<string, unknown>,
): string | undefined {
  if (label == null || label === '') return undefined
  const needle = String(label).toLowerCase()
  const options = getCachedRelationOptions(endpoint, params)
  const exact = options.find((o) => o.label.toLowerCase() === needle)
  if (exact) return exact.value
  const partial = options.find(
    (o) => o.label.toLowerCase().includes(needle) || needle.includes(o.label.toLowerCase()),
  )
  return partial?.value
}

export async function preloadRelationFields(fields: FormFieldSchema[]): Promise<void> {
  const seen = new Set<string>()
  const tasks: Promise<RelationOption[]>[] = []

  for (const field of fields) {
    if (field.type === 'guardian-section') {
      const key = cacheKey(moduleEndpoints.guardians, {})
      if (!seen.has(key)) {
        seen.add(key)
        tasks.push(loadRelationOptions(moduleEndpoints.guardians))
      }
      continue
    }
    if (field.type !== 'relation' || !field.relation) continue
    const params = field.relation.queryParams ?? field.relation.params
    const key = cacheKey(field.relation.endpoint, params)
    if (seen.has(key)) continue
    seen.add(key)
    tasks.push(loadRelationOptions(field.relation.endpoint, params))
  }

  await Promise.all(tasks)
}

export function clearRelationCache(): void {
  optionCache.clear()
}
