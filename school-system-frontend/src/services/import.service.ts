import { api } from '@/lib/api'
import { unwrapOne } from '@/lib/api-response'
import { endpoints } from './endpoints'

export type PeopleImportType = 'students' | 'teachers' | 'employees' | 'guardians'

export interface PeopleImportResult {
  created: number
  updated: number
  failed: number
  errors: Array<{ line: number; message: string }>
  logins_created?: number
  dry_run?: boolean
}

export async function downloadPeopleImportTemplate(type: PeopleImportType): Promise<void> {
  const { data } = await api.get(endpoints.imports.template(type), {
    responseType: 'blob',
  })
  const blob = data instanceof Blob ? data : new Blob([data], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = `${type}-import-template.csv`
  anchor.rel = 'noopener'
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()
  URL.revokeObjectURL(url)
}

export async function importPeopleCsv(
  type: PeopleImportType,
  file: File,
  options?: { createLoginUsers?: boolean; dryRun?: boolean },
): Promise<PeopleImportResult> {
  const form = new FormData()
  form.append('file', file)
  if (options?.createLoginUsers) {
    form.append('create_login_users', '1')
  }
  if (options?.dryRun) {
    form.append('dry_run', '1')
  }
  const { data } = await api.post(endpoints.imports.import(type), form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return unwrapOne<PeopleImportResult>(data)
}
