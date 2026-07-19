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
import { safeDisplayValue } from '@/lib/entity-display'

export function textColumn(header: string, key: string): ColumnDef<Record<string, unknown>> {
  return {
    accessorKey: key,
    header,
    cell: ({ row }) => {
      const relationLabel = relationLabelFromRow(row.original, key)
      return safeDisplayValue(key, row.getValue(key), relationLabel)
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

/** Student name with admission number when the nested student object is loaded. */
export function studentRelationColumn(
  header = 'Student',
  key = 'student',
): ColumnDef<Record<string, unknown>> {
  return {
    id: `${key}_display`,
    header,
    cell: ({ row }) => {
      const student = row.original[key] ?? row.original[toCamelCase(key)]
      if (student && typeof student === 'object') {
        const record = student as Record<string, unknown>
        const name = String(
          record.full_name
          ?? (`${record.first_name ?? ''} ${record.last_name ?? ''}`.trim() || '—'),
        )
        const number = record.student_number ? String(record.student_number) : ''
        if (!number) return name
        return h('span', { class: 'inline-flex flex-col gap-0.5' }, [
          h('span', { class: 'text-sm text-foreground' }, name),
          h('span', { class: 'font-mono text-xs text-muted-foreground' }, number),
        ])
      }

      const flatName = row.original.full_name ?? row.original.student_name
      const flatNumber = row.original.student_number
      if (flatName != null) {
        if (flatNumber == null) return String(flatName)
        return h('span', { class: 'inline-flex flex-col gap-0.5' }, [
          h('span', { class: 'text-sm text-foreground' }, String(flatName)),
          h('span', { class: 'font-mono text-xs text-muted-foreground' }, String(flatNumber)),
        ])
      }

      return '—'
    },
  }
}

function toCamelCase(value: string): string {
  return value.replace(/_([a-z])/g, (_, c: string) => c.toUpperCase())
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
  checked_in: 'default',
  checked_out: 'secondary',
  pending: 'secondary',
  partial: 'outline',
  inactive: 'secondary',
  maintenance: 'outline',
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
  if (key === 'id') {
    return {
      id: '_hidden_id',
      header: '',
      cell: () => null,
      enableHiding: true,
    }
  }
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
  return keys.filter((key) => key !== 'id').map(smartColumn)
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
          const name = String(record.full_name ?? `${record.first_name ?? ''} ${record.last_name ?? ''}`.trim())
          const number = record.student_number ? String(record.student_number) : ''
          if (!name) return ''
          return number ? `${name} (${number})` : name
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
  dateTimeColumn('Applied', 'created_at'),
]

export const examColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Exam', 'name'),
  {
    id: 'schedule',
    header: 'Schedule',
    accessorFn: (row) => row.exam_date,
    cell: ({ row }) => {
      const date = formatDate(row.original.exam_date)
      const start = row.original.start_time ? formatTime(row.original.start_time) : ''
      const end = row.original.end_time ? formatTime(row.original.end_time) : ''
      if (start && end && start !== '—' && end !== '—') return `${date} · ${start}–${end}`
      if (start && start !== '—') return `${date} · ${start}`
      return date
    },
  },
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
  studentRelationColumn(),
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
export const holidayProgramColumns = defaultColumns(['name', 'start_date', 'end_date', 'fee_amount', 'status'])
export const feeStructureColumns: ColumnDef<Record<string, unknown>>[] = [
  {
    id: 'class_name',
    header: 'Class',
    cell: ({ row }) => {
      const classModel = row.original.class_model ?? row.original.classModel
      if (classModel && typeof classModel === 'object') {
        return String((classModel as Record<string, unknown>).name ?? row.original.class_name ?? '—')
      }
      return String(row.original.class_name ?? '—')
    },
  },
  {
    id: 'category',
    header: 'Category',
    cell: ({ row }) => {
      const category = row.original.fee_category ?? row.original.feeCategory
      const name = category && typeof category === 'object'
        ? String((category as Record<string, unknown>).name ?? row.original.category ?? '—')
        : String(row.original.category ?? '—')
      const categoryId = row.original.fee_category_id
        ?? (category && typeof category === 'object' ? (category as Record<string, unknown>).id : null)
      if (categoryId == null) return name
      return h(
        RouterLink,
        {
          to: { path: '/finance/fee-categories', query: { highlight: String(categoryId) } },
          class: 'text-sm font-medium text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
        },
        { default: () => name },
      )
    },
  },
  currencyColumn('Amount', 'amount'),
  textColumn('Currency', 'currency'),
]

export const feeCategoryColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  textColumn('Description', 'description'),
  {
    id: 'is_active',
    header: 'Status',
    cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
  },
  {
    id: 'fee_structures_count',
    header: 'Fee structures',
    cell: ({ row }) => {
      const count = Number(row.original.fee_structures_count ?? 0)
      const id = row.original.id
      const label = `${count} structure${count === 1 ? '' : 's'}`
      if (id == null) return label
      return h(
        RouterLink,
        {
          to: { path: '/finance/fees', query: { fee_category_id: String(id) } },
          class: 'text-sm font-medium text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
          'aria-label': `View ${label} for this category`,
        },
        { default: () => label },
      )
    },
  },
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

export const termColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  textColumn('Academic year', 'academic_year'),
  dateColumn('Start', 'start_date'),
  dateColumn('End', 'end_date'),
  textColumn('Order', 'order'),
  {
    id: 'is_current',
    header: 'Current',
    cell: ({ row }) => {
      const current = row.original.is_current === true || row.original.is_current === 1
      return h(Badge, { variant: current ? 'default' : 'outline' }, () => (current ? 'Current' : '—'))
    },
  },
  {
    id: 'is_active',
    header: 'Status',
    cell: ({ row }) => {
      const active = row.original.is_active !== false && row.original.is_active !== 0
      return h(Badge, { variant: active ? 'default' : 'secondary' }, () => (active ? 'Active' : 'Inactive'))
    },
  },
]
export const threadColumns = defaultColumns(['subject', 'status', 'created_at'])
export const leaveColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Staff', 'teacher_name'),
  textColumn('Type', 'type'),
  dateColumn('Start', 'start_date'),
  dateColumn('End', 'end_date'),
  textColumn('Days', 'days'),
  statusColumn(),
  textColumn('Reviewed by', 'reviewed_by'),
]
export const disciplineColumns: ColumnDef<Record<string, unknown>>[] = [
  dateColumn('Date', 'incident_date'),
  textColumn('Category', 'category'),
  textColumn('Severity', 'severity'),
  studentRelationColumn(),
]
export const complianceColumns = defaultColumns(['title', 'category', 'status'])
export const consentColumns = defaultColumns(['title', 'status', 'created_at'])
export const auditColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Module', 'module'),
  textColumn('Action', 'action'),
  personNameColumn('User', 'user'),
  dateTimeColumn('When', 'created_at'),
]
export const inventoryColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  textColumn('SKU', 'sku'),
  textColumn('Type', 'type'),
  textColumn('Size', 'size'),
  {
    id: 'stock_quantity',
    header: 'Stock',
    cell: ({ row }) => {
      const stock = Number(row.original.stock_quantity ?? 0)
      const reorder = Number(row.original.reorder_level ?? 0)
      const low = reorder > 0 && stock <= reorder
      return h(
        'span',
        { class: low ? 'font-medium text-destructive' : undefined },
        low ? `${stock} (low)` : String(stock),
      )
    },
  },
  currencyColumn('Unit price', 'unit_price'),
  {
    id: 'billing_mode',
    header: 'Billing',
    cell: ({ row }) => {
      const mode = String(row.original.billing_mode ?? '—')
      const labels: Record<string, string> = {
        direct_sale: 'Direct sale',
        mandatory_fee: 'Mandatory fee',
        both: 'Both',
      }
      return labels[mode] ?? mode
    },
  },
  {
    id: 'is_active',
    header: 'Status',
    cell: ({ row }) => {
      const active = row.original.is_active !== false && row.original.is_active !== 0
      return h(Badge, { variant: active ? 'default' : 'secondary' }, () => (active ? 'Active' : 'Inactive'))
    },
  },
]
export const procurementColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Title', 'title'),
  nestedColumn('Department', 'department', 'name'),
  currencyColumn('Est. cost', 'estimated_cost'),
  statusColumn(),
]
export const libraryColumns = defaultColumns(['title', 'author', 'isbn', 'status'])
export const transportColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Registration', 'registration_number'),
  textColumn('Make', 'make'),
  textColumn('Model', 'model'),
  textColumn('Capacity', 'capacity'),
  statusColumn(),
]

export const transportDriverColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  textColumn('License', 'license_number'),
  textColumn('Phone', 'phone'),
  statusColumn(),
]

export const transportRouteColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  {
    id: 'vehicle',
    header: 'Vehicle',
    cell: ({ row }) => {
      const vehicle = row.original.vehicle as Record<string, unknown> | undefined
      if (vehicle && typeof vehicle === 'object') {
        return String(vehicle.registration_number ?? vehicle.make ?? '—')
      }
      return '—'
    },
  },
  {
    id: 'driver',
    header: 'Driver',
    cell: ({ row }) => {
      const driver = row.original.driver as Record<string, unknown> | undefined
      if (driver && typeof driver === 'object') {
        return String(driver.name ?? '—')
      }
      return '—'
    },
  },
  textColumn('Description', 'route_description'),
  statusColumn(),
]

export const assetColumns = defaultColumns(['name', 'category', 'purchase_date', 'status'])
export const hostelColumns = defaultColumns(['name', 'capacity', 'gender', 'status'])
export const visitorColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  textColumn('Phone', 'phone'),
  textColumn('ID number', 'id_number'),
  textColumn('Purpose', 'purpose'),
  {
    id: 'host',
    header: 'Host',
    cell: ({ row }) => {
      const host = (row.original.host ?? row.original.host_user) as Record<string, unknown> | undefined
      if (host && typeof host === 'object') {
        return String(host.name ?? host.email ?? '—')
      }
      return '—'
    },
  },
  studentRelationColumn(),
  dateTimeColumn('Checked in', 'check_in_at'),
  dateTimeColumn('Checked out', 'check_out_at'),
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = String(row.original.status ?? '—').replaceAll('_', ' ')
      const raw = String(row.original.status ?? '').toLowerCase()
      const variant = statusVariants[raw] ?? 'outline'
      return h(Badge, { variant }, () => status)
    },
  },
]

export const healthColumns: ColumnDef<Record<string, unknown>>[] = [
  studentRelationColumn(),
  dateColumn('Visit date', 'visit_date'),
  textColumn('Complaint', 'complaint'),
  textColumn('Diagnosis', 'diagnosis'),
]
export const eventColumns = defaultColumns(['title', 'starts_at', 'location', 'status'])
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
  textColumn('Student #', 'student_number'),
  textColumn('Class', 'class'),
  currencyColumn('Balance', 'balance'),
  viewActionColumn('parent-child-detail'),
]

export const genericColumns = defaultColumns(['name', 'status', 'created_at'])

export const userColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Name', 'name'),
  textColumn('Email', 'email'),
  textColumn('Role', 'role'),
  statusColumn(),
]

export const paymentColumns: ColumnDef<Record<string, unknown>>[] = [
  dateColumn('Date', 'date'),
  studentRelationColumn(),
  nestedColumn('Invoice', 'invoice', 'invoice_number'),
  currencyColumn('Amount', 'amount'),
  textColumn('Method', 'method'),
  textColumn('Reference', 'reference'),
  statusColumn(),
]

export const invoiceColumns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Invoice #', 'invoice_number'),
  studentRelationColumn(),
  currencyColumn('Amount', 'amount'),
  currencyColumn('Paid', 'amount_paid'),
  currencyColumn('Balance', 'balance'),
  dateColumn('Due', 'due_date'),
  statusColumn(),
]
