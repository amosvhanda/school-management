import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { findRelationIdByLabel, findRelationLabel, findRelationRaw } from '@/lib/relation-options'
import { moduleEndpoints } from '@/services'
import { mapLeaveFormToPayload } from '@/modules/hr/leave-form'
import { studentGuardianMetaFields } from '@/modules/students/student-form'

const rowAliases: Record<string, Record<string, string>> = {
  students: {
    firstName: 'first_name',
    surname: 'last_name',
    dateOfBirth: 'date_of_birth',
    guardianFirstName: 'guardian_first_name',
    guardianSurname: 'guardian_last_name',
    guardianPhone: 'guardian_phone',
    guardianEmail: 'guardian_email',
    guardianRelationship: 'guardian_relationship',
  },
  teachers: {
    firstName: 'first_name',
    surname: 'last_name',
    joiningDate: 'joining_date',
  },
}

export function getValueByPath(obj: Record<string, unknown>, path: string): unknown {
  if (!path.includes('.')) return obj[path]
  return path.split('.').reduce<unknown>((acc, key) => {
    if (acc && typeof acc === 'object') return (acc as Record<string, unknown>)[key]
    return undefined
  }, obj)
}

function readGuardianField(row: Record<string, unknown>, fieldKey: string): unknown {
  const primary = resolvePrimaryGuardian(row)
  if (primary) {
    const camel = fieldKey.replace(/_([a-z])/g, (_, c: string) => c.toUpperCase())
    return primary[fieldKey] ?? primary[camel] ?? primary[fieldKey.replace('_', '')]
  }

  const nested = row.guardian
  if (nested && typeof nested === 'object') {
    const g = nested as Record<string, unknown>
    return g[fieldKey] ?? g[fieldKey.replace('_', '')]
  }
  return row[`guardian_${fieldKey}`]
}

function resolvePrimaryGuardian(row: Record<string, unknown>): Record<string, unknown> | null {
  const guardians = row.guardians
  if (Array.isArray(guardians) && guardians.length) {
    const primary = guardians.find((item) => {
      if (!item || typeof item !== 'object') return false
      const pivot = (item as Record<string, unknown>).pivot
      return pivot && typeof pivot === 'object' && (pivot as Record<string, unknown>).is_primary
    }) ?? guardians[0]
    return primary as Record<string, unknown>
  }

  const nested = row.guardian
  if (nested && typeof nested === 'object' && (nested as Record<string, unknown>).id != null) {
    return nested as Record<string, unknown>
  }

  return null
}

/** Fields used for row ↔ form mapping (includes virtual guardian meta fields). */
export function getMappingFields(
  listKey: string | undefined,
  fields: FormFieldSchema[],
): FormFieldSchema[] {
  const visible = fields.filter((f) => f.type !== 'guardian-section')
  if (listKey === 'students') {
    return [...visible, ...studentGuardianMetaFields]
  }
  return visible
}

function resolveGuardianMode(row: Record<string, unknown>): 'existing' | 'new' | 'none' {
  const primary = resolvePrimaryGuardian(row)
  if (primary?.id != null) return 'existing'

  const nested = row.guardian
  if (nested && typeof nested === 'object' && (nested as Record<string, unknown>).id != null) {
    return 'existing'
  }

  const hasFlat =
    row.guardian_first_name
    ?? row.guardian_firstName
    ?? readGuardianField(row, 'first_name')
    ?? readGuardianField(row, 'phone')

  if (hasFlat) return 'new'

  return 'none'
}

function coerceFieldValue(field: FormFieldSchema, raw: unknown): unknown {
  if (raw == null) raw = ''

  if (field.type === 'relation' || field.type === 'select') {
    return raw === '' ? '' : String(raw)
  }

  if (field.type === 'number') {
    if (raw === '') return ''
    return Number(raw)
  }

  if (field.type === 'checkbox') {
    return Boolean(raw)
  }

  return raw
}

function resolveRelationValue(field: FormFieldSchema, row: Record<string, unknown>): string {
  if (field.type !== 'relation' || !field.relation) return ''

  const params = field.relation.queryParams ?? field.relation.params
  const idPath = field.rowKey ?? field.relation.rowKey ?? field.name
  const id = getValueByPath(row, idPath) ?? row[field.name]

  if (id != null && id !== '') return String(id)

  const fallbackKey = field.relation.fallbackRowKey
  if (fallbackKey) {
    const label = getValueByPath(row, fallbackKey) ?? row[fallbackKey]
    const matched = findRelationIdByLabel(field.relation.endpoint, label, params)
    if (matched) return matched
  }

  return ''
}

export function mapRowToFormValues(
  listKey: string | undefined,
  row: Record<string, unknown>,
  fields: FormFieldSchema[],
): Record<string, unknown> {
  const mappingFields = getMappingFields(listKey, fields)
  const aliases = listKey ? rowAliases[listKey] ?? {} : {}
  const values: Record<string, unknown> = {}

  if (listKey === 'students') {
    values.guardianMode = resolveGuardianMode(row)
  }

  for (const field of mappingFields) {
    if (field.type === 'relation') {
      if (field.name === 'guardian_id') {
        const primary = resolvePrimaryGuardian(row)
        values[field.name] = primary?.id != null ? String(primary.id) : ''
        continue
      }
      if (listKey === 'guardians' && field.name === 'student_id') {
        const students = row.students
        if (Array.isArray(students) && students.length) {
          values[field.name] = String((students[0] as Record<string, unknown>).id ?? '')
        } else {
          values[field.name] = ''
        }
        continue
      }
      values[field.name] = resolveRelationValue(field, row)
      continue
    }

    if (field.name === 'guardianRelationship') {
      const primary = resolvePrimaryGuardian(row)
      const pivot = primary?.pivot as Record<string, unknown> | undefined
      if (pivot?.relationship) {
        values[field.name] = String(pivot.relationship)
        continue
      }
    }

    let raw: unknown

    if (field.rowKey?.startsWith('guardian.')) {
      raw = getValueByPath(row, field.rowKey) ?? readGuardianField(row, field.rowKey.replace('guardian.', ''))
    } else if (field.rowKey) {
      raw = getValueByPath(row, field.rowKey)
    } else {
      const apiKey = aliases[field.name] ?? field.name
      raw = row[apiKey] ?? row[field.name]
    }

    values[field.name] = coerceFieldValue(field, raw)
  }

  return values
}

function mapStudentPayload(
  values: Record<string, unknown>,
  fields: FormFieldSchema[],
): Record<string, unknown> {
  const payload: Record<string, unknown> = {}
  const guardian: Record<string, unknown> = {}
  const mode = (values.guardianMode as string) ?? 'new'

  for (const field of fields) {
    const val = values[field.name]
    if (field.name === 'guardian_id' || field.name === 'guardianMode') continue

    if (val === '' || val == null) continue

    if (field.payloadPath?.startsWith('guardian.')) {
      if (mode !== 'new') continue
      if (field.name === 'guardianRelationship') {
        guardian.relationship = val
      } else {
        guardian[field.payloadPath.slice('guardian.'.length)] = val
      }
      continue
    }

    payload[field.name] = val
  }

  if (mode === 'existing') {
    if (values.guardian_id) {
      payload.guardian_id = Number(values.guardian_id)
    }
    if (values.guardianRelationship) {
      payload.guardian = { relationship: values.guardianRelationship }
    }
  } else if (mode === 'new' && Object.keys(guardian).length) {
    payload.guardian = guardian
  }

  if (payload.class_id) {
    payload.class_id = Number(payload.class_id)
    const className = findRelationLabel(moduleEndpoints.classes, payload.class_id)
    if (className) {
      payload.class = className.split(' · ')[0]
    }
  }

  if (payload.grade_level_id) {
    payload.grade_level_id = Number(payload.grade_level_id)
  } else {
    delete payload.grade_level_id
  }

  if (payload.dateOfBirth === '') delete payload.dateOfBirth
  delete payload.date_of_birth

  return payload
}

export function mapFormToPayload(
  listKey: string | undefined,
  values: Record<string, unknown>,
  fields: FormFieldSchema[] = [],
): Record<string, unknown> {
  const mappingFields = getMappingFields(listKey, fields)

  if (listKey === 'students') {
    return mapStudentPayload(values, mappingFields)
  }

  if (listKey === 'finance-payments') {
    const payload: Record<string, unknown> = {
      invoice_id: values.invoice_id ? Number(values.invoice_id) : undefined,
      amount: values.amount,
      method: values.method,
    }
    if (values.reference) payload.reference = values.reference
    if (values.notes) payload.notes = values.notes
    return payload
  }

  if (listKey === 'finance-invoices') {
    return {
      student_id: values.student_id ? Number(values.student_id) : undefined,
      description: values.description,
      amount: values.amount,
      due_date: values.due_date,
    }
  }

  if (listKey === 'guardians') {
    const payload = { ...values }
    if (payload.student_id === '' || payload.student_id == null) {
      delete payload.student_id
    } else {
      payload.student_id = Number(payload.student_id)
    }
    if (payload.relationship === '') delete payload.relationship
    if (payload.email === '') delete payload.email
    return payload
  }

  if (listKey === 'comms-announcements') {
    return {
      title: String(values.title ?? '').trim(),
      message: String(values.message ?? '').trim(),
      type: values.type ?? 'info',
      target_audience: values.target_audience ?? values.audience ?? 'all',
      date: values.date,
      is_active: values.is_active !== false,
    }
  }

  if (listKey === 'hr-leave') {
    return mapLeaveFormToPayload(values)
  }

  if (listKey === 'academics-assignments') {
    const subjectLabel = values.subject_id
      ? findRelationLabel(moduleEndpoints.subjects, values.subject_id)
      : undefined
    return {
      title: values.title,
      subject: subjectLabel?.split(' · ')[0] ?? values.subject,
      class_id: values.class_id ? Number(values.class_id) : undefined,
      teacher_id: values.teacher_id ? Number(values.teacher_id) : undefined,
      due_date: values.due_date,
    }
  }

  if (listKey === 'ops-inventory-sales') {
    return {
      items: [
        {
          item_id: values.item_id ? Number(values.item_id) : undefined,
          quantity: values.quantity,
        },
      ],
      payment_method: values.payment_method,
      ...(values.student_id ? { student_id: Number(values.student_id) } : {}),
    }
  }

  if (listKey === 'finance-fees') {
    return {
      class_id: values.class_id ? Number(values.class_id) : undefined,
      fee_category_id: values.fee_category_id ? Number(values.fee_category_id) : undefined,
      amount: values.amount,
      currency: values.currency,
    }
  }

  if (listKey === 'finance-fee-categories') {
    return {
      name: values.name,
      description: values.description || null,
      is_active: values.is_active !== false,
      ...(values.order != null && values.order !== '' ? { order: Number(values.order) } : {}),
    }
  }

  if (listKey === 'academics-terms') {
    return {
      name: String(values.name ?? '').trim(),
      academic_year: String(values.academic_year ?? '').trim(),
      start_date: values.start_date,
      end_date: values.end_date,
      description: values.description ? String(values.description).trim() : null,
      is_current: values.is_current === true,
      is_active: values.is_active !== false,
      ...(values.order != null && values.order !== '' ? { order: Number(values.order) } : {}),
    }
  }

  if (listKey === 'ops-inventory') {
    return {
      name: String(values.name ?? '').trim(),
      sku: values.sku ? String(values.sku).trim() : null,
      type: values.type ?? 'uniform',
      size: values.size ? String(values.size).trim() : null,
      description: values.description ? String(values.description).trim() : null,
      stock_quantity: Number(values.stock_quantity ?? 0),
      reorder_level: values.reorder_level != null && values.reorder_level !== ''
        ? Number(values.reorder_level)
        : 5,
      unit_price: Number(values.unit_price ?? 0),
      currency: values.currency ?? 'USD',
      billing_mode: values.billing_mode ?? 'direct_sale',
      is_active: values.is_active !== false,
    }
  }

  if (listKey === 'ops-transport') {
    return {
      registration_number: String(values.registration_number ?? '').trim(),
      make: values.make ? String(values.make).trim() : null,
      model: values.model ? String(values.model).trim() : null,
      ...(values.capacity != null && values.capacity !== '' ? { capacity: Number(values.capacity) } : {}),
      status: values.status ?? 'active',
    }
  }

  if (listKey === 'ops-transport-drivers') {
    return {
      name: String(values.name ?? '').trim(),
      license_number: values.license_number ? String(values.license_number).trim() : null,
      phone: values.phone ? String(values.phone).trim() : null,
      status: values.status ?? 'active',
    }
  }

  if (listKey === 'ops-transport-routes') {
    return {
      name: String(values.name ?? '').trim(),
      vehicle_id: values.vehicle_id ? Number(values.vehicle_id) : null,
      driver_id: values.driver_id ? Number(values.driver_id) : null,
      route_description: values.route_description ? String(values.route_description).trim() : null,
      status: values.status ?? 'active',
    }
  }

  if (listKey === 'ops-visitors') {
    return {
      name: String(values.name ?? '').trim(),
      purpose: String(values.purpose ?? '').trim(),
      phone: values.phone ? String(values.phone).trim() : null,
      id_number: values.id_number ? String(values.id_number).trim() : null,
      host_user_id: values.host_user_id ? Number(values.host_user_id) : null,
      student_id: values.student_id ? Number(values.student_id) : null,
    }
  }

  if (listKey === 'ops-procurement') {
    return {
      title: values.title,
      ...(values.department_id ? { department_id: Number(values.department_id) } : {}),
      items: [
        {
          description: values.item_description,
          quantity: values.item_quantity,
          unit_cost: values.item_unit_cost,
        },
      ],
      submit: values.submit === true,
    }
  }

  const payload = { ...values }

  for (const field of mappingFields) {
    const val = payload[field.name]
    if (field.type === 'relation') {
      if (val === '' || val == null) {
        delete payload[field.name]
      } else {
        payload[field.name] = Number(val)
      }
    }
  }

  if (listKey === 'teachers') {
    return mapTeacherPayload(payload)
  }

  return payload
}

function mapTeacherPayload(values: Record<string, unknown>): Record<string, unknown> {
  const payload: Record<string, unknown> = { ...values }

  if (payload.subject_id) {
    const subject = findRelationRaw(moduleEndpoints.subjects, payload.subject_id)
    payload.subject = subject?.name ?? findRelationLabel(moduleEndpoints.subjects, payload.subject_id)
  }
  delete payload.subject_id

  if (payload.department_id) {
    const department = findRelationRaw(moduleEndpoints.departments, payload.department_id)
    payload.department = department?.name ?? findRelationLabel(moduleEndpoints.departments, payload.department_id)
  }
  delete payload.department_id

  for (const key of ['phone', 'address', 'subject', 'department', 'qualification', 'joiningDate']) {
    if (payload[key] === '') delete payload[key]
  }

  delete payload.name
  return payload
}

export const backendCrudSupport: Record<string, { create: boolean; update: boolean; delete: boolean }> = {
  students: { create: true, update: true, delete: true },
  teachers: { create: true, update: true, delete: true },
  guardians: { create: true, update: true, delete: false },
  'academics-setup': { create: true, update: true, delete: true },
  'academics-subjects': { create: true, update: true, delete: true },
  'academics-departments': { create: true, update: true, delete: true },
  'academics-grade-levels': { create: true, update: true, delete: true },
  'academics-grading-scales': { create: true, update: true, delete: true },
  'academics-rooms': { create: true, update: true, delete: true },
  'academics-terms': { create: true, update: true, delete: true },
  'academics-assignments': { create: true, update: true, delete: true },
  'academics-tests': { create: true, update: true, delete: true },
  'academics-teacher-assignments': { create: true, update: true, delete: true },
  'academics-attendance': { create: true, update: false, delete: false },
  'academics-timetable': { create: true, update: true, delete: true },
  'academics-holiday-programs': { create: true, update: true, delete: false },
  'academics-exams': { create: true, update: true, delete: true },
  'finance-payments': { create: true, update: false, delete: false },
  'finance-invoices': { create: true, update: true, delete: false },
  'finance-fees': { create: true, update: true, delete: true },
  'finance-fee-categories': { create: true, update: true, delete: true },
  'finance-payroll': { create: false, update: true, delete: false },
  'ops-inventory': { create: true, update: true, delete: false },
  'ops-transport': { create: true, update: true, delete: false },
  'ops-transport-drivers': { create: true, update: true, delete: false },
  'ops-transport-routes': { create: true, update: true, delete: false },
  'ops-visitors': { create: true, update: false, delete: false },
  'comms-announcements': { create: true, update: true, delete: true },
  'hr-leave': { create: true, update: false, delete: false },
  'settings-custom-fields': { create: true, update: false, delete: true },
}
