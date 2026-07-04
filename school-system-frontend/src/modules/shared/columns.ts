import { h } from 'vue'
import { RouterLink } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import { Badge } from '@/components/ui/badge'
import {
  formatDate,
  formatDateTime,
  formatTime,
  headerFromKey,
  isDateFieldKey,
  isDateTimeFieldKey,
  isTimeFieldKey,
} from '@/lib/format'
import { relationLabelFromRow } from '@/lib/relation-display'

export function textColumn(header: string, key: string): ColumnDef<Record<string, unknown>> {
  return {
    accessorKey: key,
    header,
    cell: ({ row }) => {
      const relationLabel = relationLabelFromRow(row.original, key)
      if (relationLabel) return relationLabel
      return String(row.getValue(key) ?? '—')
    },
  }
}

export function nestedColumn(header: string, key: string, nestedKey: string): ColumnDef<Record<string, unknown>> {
  return {
    id: key,
    header,
    cell: ({ row }) => {
      const val = row.original[key]
      if (val && typeof val === 'object') {
        return String((val as Record<string, unknown>)[nestedKey] ?? '—')
      }
      return '—'
    },
  }
}

/** Renders full_name, name, or first/last name from a row or nested object key. */
export function personNameColumn(
  header: string,
  key?: string,
): ColumnDef<Record<string, unknown>> {
  return {
    id: key ? `person-${key}` : 'person-name',
    header,
    cell: ({ row }) => {
      const source = key ? row.original[key] : row.original
      if (!source || typeof source !== 'object') {
        const flat = row.original
        return String(
          flat.full_name
          ?? flat.fullName
          ?? flat.name
          ?? (`${flat.first_name ?? ''} ${flat.last_name ?? ''}`.trim() || '—'),
        )
      }
      const obj = source as Record<string, unknown>
      return String(
        obj.full_name
        ?? obj.fullName
        ?? obj.name
        ?? (`${obj.first_name ?? ''} ${obj.last_name ?? ''}`.trim() || '—'),
      )
    },
  }
}

const statusVariants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  active: 'default',
  completed: 'default',
  paid: 'default',
  pending: 'secondary',
  partial: 'outline',
  inactive: 'secondary',
  absent: 'destructive',
  overdue: 'destructive',
  cancelled: 'destructive',
}

export function statusColumn(header = 'Status', key = 'status'): ColumnDef<Record<string, unknown>> {
  return {
    accessorKey: key,
    header,
    cell: ({ row }) => {
      const status = String(row.getValue(key) ?? '—')
      const variant = statusVariants[status.toLowerCase()] ?? 'outline'
      return h(Badge, { variant }, () => status)
    },
  }
}

export function currencyColumn(header: string, amountKey: string, currencyKey = 'currency'): ColumnDef<Record<string, unknown>> {
  return {
    id: `${amountKey}-currency`,
    header,
    cell: ({ row }) => {
      const amount = Number(row.original[amountKey] ?? 0)
      const currency = String(row.original[currencyKey] ?? 'USD')
      return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
    },
  }
}

export function dateColumn(header: string, key: string): ColumnDef<Record<string, unknown>> {
  return {
    accessorKey: key,
    header,
    cell: ({ row }) => formatDate(row.getValue(key)),
  }
}

export function dateTimeColumn(header: string, key: string): ColumnDef<Record<string, unknown>> {
  return {
    accessorKey: key,
    header,
    cell: ({ row }) => formatDateTime(row.getValue(key)),
  }
}

export function timeColumn(header: string, key: string): ColumnDef<Record<string, unknown>> {
  return {
    accessorKey: key,
    header,
    cell: ({ row }) => formatTime(row.getValue(key)),
  }
}

function smartColumn(key: string): ColumnDef<Record<string, unknown>> {
  if (key === 'status') return statusColumn()
  if (isDateTimeFieldKey(key)) return dateTimeColumn(headerFromKey(key), key)
  if (isDateFieldKey(key)) return dateColumn(headerFromKey(key), key)
  if (isTimeFieldKey(key)) return timeColumn(headerFromKey(key), key)
  return textColumn(headerFromKey(key), key)
}

export function viewActionColumn(routeName: string, idKey = 'id'): ColumnDef<Record<string, unknown>> {
  return {
    id: 'actions',
    header: '',
    cell: ({ row }) => {
      const id = row.original[idKey]
      if (id == null) return null
      return h(
        RouterLink,
        { to: { name: routeName, params: { id: String(id) } }, class: 'text-sm font-medium text-primary hover:underline' },
        { default: () => 'View' },
      )
    },
  }
}

export function defaultColumns(keys: string[]): ColumnDef<Record<string, unknown>>[] {
  return keys.map(smartColumn)
}

export const studentColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Student #', 'student_number'),
  textColumn('Name', 'full_name'),
  {
    id: 'class',
    header: 'Class',
    cell: ({ row }) => {
      const classModel = row.original.class_model ?? row.original.classModel
      if (classModel && typeof classModel === 'object') {
        const name = String((classModel as Record<string, unknown>).name ?? '')
        const form = (classModel as Record<string, unknown>).form
        return form ? `${name} · ${form}` : name || '—'
      }
      return String(row.original.class ?? '—')
    },
  },
  {
    id: 'grade_level',
    header: 'Grade',
    cell: ({ row }) => {
      const grade = row.original.grade_level ?? row.original.gradeLevel
      if (grade && typeof grade === 'object') {
        return String((grade as Record<string, unknown>).name ?? '—')
      }
      return '—'
    },
  },
  statusColumn(),
  viewActionColumn('student-detail'),
]

export const teacherColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Employee #', 'employee_id'),
  personNameColumn('Name'),
  textColumn('Email', 'email'),
  textColumn('Subject', 'subject'),
  textColumn('Department', 'department'),
  statusColumn(),
]

export const guardianColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('First name', 'first_name'),
  textColumn('Last name', 'last_name'),
  textColumn('Phone', 'phone'),
  textColumn('Email', 'email'),
  {
    id: 'relationship',
    header: 'Relationship',
    cell: ({ row }) => {
      const direct = row.original.relationship
      if (direct != null && String(direct).trim() !== '') return String(direct)

      const students = row.original.students
      if (!Array.isArray(students) || !students.length) return '—'

      const relationships = students
        .map((student) => {
          if (!student || typeof student !== 'object') return ''
          const pivot = (student as Record<string, unknown>).pivot
          if (!pivot || typeof pivot !== 'object') return ''
          return String((pivot as Record<string, unknown>).relationship ?? '').trim()
        })
        .filter(Boolean)

      if (!relationships.length) return '—'

      return Array.from(new Set(relationships)).join(', ')
    },
  },
  {
    id: 'linked_students',
    header: 'Linked students',
    cell: ({ row }) => {
      const students = row.original.students
      if (!Array.isArray(students) || !students.length) return '—'
      return students
        .map((student) => {
          if (!student || typeof student !== 'object') return ''
          const record = student as Record<string, unknown>
          return String(record.full_name ?? `${record.first_name ?? ''} ${record.last_name ?? ''}`.trim())
        })
        .filter(Boolean)
        .join(', ')
    },
  },
]

export const enrollmentColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('First name', 'first_name'),
  textColumn('Surname', 'surname'),
  textColumn('Grade', 'grade_applying_for'),
  textColumn('Year', 'academic_year'),
  statusColumn(),
  dateColumn('Applied', 'created_at'),
]

export const examColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Exam', 'name'),
  dateColumn('Date', 'exam_date'),
  nestedColumn('Term', 'term', 'name'),
  nestedColumn('Grade', 'grade_level', 'name'),
  nestedColumn('Subject', 'subject', 'name'),
  textColumn('Year', 'academic_year'),
  {
    id: 'marks',
    header: 'Marks',
    cell: ({ row }) => String(row.original.total_marks ?? '—'),
  },
]

export const attendanceColumns: ColumnDef<Record<string, unknown>>[] = [
  dateColumn('Date', 'date'),
  nestedColumn('Student', 'student', 'full_name'),
  nestedColumn('Class', 'class_model', 'name'),
  statusColumn(),
]

export const timetableColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Day', 'day'),
  timeColumn('Start', 'start_time'),
  timeColumn('End', 'end_time'),
  nestedColumn('Class', 'class_model', 'name'),
  nestedColumn('Subject', 'subject', 'name'),
]
export const holidayProgramColumns = defaultColumns(['name', 'start_date', 'end_date', 'fee', 'status'])
export const feeStructureColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  currencyColumn('Amount', 'amount'),
  textColumn('Grade', 'grade_level'),
  textColumn('Term', 'term'),
]

export const payrollColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Employee', 'employee_name'),
  textColumn('Month', 'month'),
  textColumn('Year', 'year'),
  currencyColumn('Gross', 'gross_salary'),
  currencyColumn('Net', 'net_salary'),
  statusColumn(),
]

export const announcementColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Title', 'title'),
  textColumn('Type', 'type'),
  textColumn('Audience', 'target_audience'),
  dateColumn('Date', 'date'),
  {
    id: 'is_active',
    header: 'Status',
    cell: ({ row }) => (row.original.is_active === false ? 'Hidden' : 'Visible'),
  },
  dateTimeColumn('Created', 'created_at'),
]
export const threadColumns = defaultColumns(['subject', 'status', 'created_at'])
export const leaveColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Staff', 'teacher_name'),
  textColumn('Type', 'type'),
  textColumn('Start', 'start_date'),
  textColumn('End', 'end_date'),
  textColumn('Days', 'days'),
  statusColumn(),
  textColumn('Reviewed by', 'reviewed_by'),
]
export const disciplineColumns: ColumnDef<Record<string, unknown>>[] = [
  dateColumn('Date', 'incident_date'),
  textColumn('Severity', 'severity'),
  statusColumn(),
  nestedColumn('Student', 'student', 'full_name'),
]
export const complianceColumns = defaultColumns(['title', 'category', 'status'])
export const consentColumns = defaultColumns(['title', 'status', 'created_at'])
export const auditColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Module', 'module'),
  textColumn('Action', 'action'),
  personNameColumn('User', 'user'),
  dateTimeColumn('When', 'created_at'),
]
export const inventoryColumns = defaultColumns(['name', 'sku', 'quantity', 'status'])
export const procurementColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Title', 'title'),
  nestedColumn('Department', 'department', 'name'),
  currencyColumn('Est. cost', 'estimated_cost'),
  statusColumn(),
]
export const libraryColumns = defaultColumns(['title', 'author', 'isbn', 'status'])
export const transportColumns = defaultColumns(['registration_number', 'make', 'capacity', 'status'])
export const assetColumns = defaultColumns(['name', 'category', 'purchase_date', 'status'])
export const hostelColumns = defaultColumns(['name', 'capacity', 'gender', 'status'])
export const visitorColumns = defaultColumns(['full_name', 'purpose', 'check_in', 'status'])
export const healthColumns: ColumnDef<Record<string, unknown>>[] = [
  nestedColumn('Student', 'student', 'full_name'),
  dateColumn('Visit date', 'visit_date'),
  textColumn('Diagnosis', 'diagnosis'),
  statusColumn(),
]
export const eventColumns = defaultColumns(['title', 'event_date', 'location', 'status'])
export const roleColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Role', 'name'),
  textColumn('Slug', 'slug'),
  textColumn('Users', 'user_count'),
]
export const workflowColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Workflow', 'workflow_name'),
  textColumn('Subject', 'subject_label'),
  textColumn('Module', 'module'),
  textColumn('Step', 'current_step_name'),
  textColumn('Requested by', 'initiated_by_name'),
  statusColumn(),
]
export const portalChildColumns: ColumnDef<Record<string, unknown>>[] = [
  personNameColumn('Name'),
  textColumn('Class', 'class'),
  currencyColumn('Balance', 'balance'),
  viewActionColumn('parent-child-detail'),
]

export const genericColumns = defaultColumns(['id', 'name', 'status', 'created_at'])

export const userColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  textColumn('Email', 'email'),
  textColumn('Role', 'role'),
  statusColumn(),
]

export const paymentColumns: ColumnDef<Record<string, unknown>>[] = [
  dateColumn('Date', 'date'),
  nestedColumn('Student', 'student', 'full_name'),
  nestedColumn('Invoice', 'invoice', 'invoice_number'),
  currencyColumn('Amount', 'amount'),
  textColumn('Method', 'method'),
  textColumn('Reference', 'reference'),
  statusColumn(),
]

export const invoiceColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Invoice #', 'invoice_number'),
  nestedColumn('Student', 'student', 'full_name'),
  currencyColumn('Amount', 'amount'),
  currencyColumn('Paid', 'amount_paid'),
  currencyColumn('Balance', 'balance'),
  dateColumn('Due', 'due_date'),
  statusColumn(),
]
