import { genderOptions } from '@/lib/form-standards'
import { INVOICE_STATUS_OPTIONS, PAYMENT_METHOD_OPTIONS, PAYMENT_STATUS_OPTIONS, PAYROLL_STATUS_OPTIONS } from '@/lib/finance-constants'
import { moduleEndpoints } from '@/services'

export interface ListFilterOption {
  label: string
  value: string
}

export interface ListRelationFilterConfig {
  endpoint: string
  moduleLabel?: string
  /** Query param sent when a parent filter is set */
  dependsOn?: {
    field: string
    paramKey?: string
  }
}

export interface ListFilterSchema {
  /** Query parameter name sent to the API */
  key: string
  label: string
  type: 'select' | 'relation'
  placeholder?: string
  options?: ListFilterOption[]
  relation?: ListRelationFilterConfig
  clearable?: boolean
}

export type ListFilterMode = 'server' | 'client'

export interface ListPageMeta {
  filters?: ListFilterSchema[]
  /** When true, search box queries the API via `search` param */
  serverSearch?: boolean
  /** `server` reloads from API; `client` filters loaded rows in the browser */
  filterMode?: ListFilterMode
  /** Use API pagination (page/per_page) instead of loading all rows */
  serverPagination?: boolean
  perPage?: number
}

const recordStatusOptions: ListFilterOption[] = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Graduated', value: 'graduated' },
  { label: 'Transferred', value: 'transferred' },
]

export const moduleListMetaRegistry: Record<string, ListPageMeta> = {
  students: {
    serverSearch: true,
    filterMode: 'server',
    serverPagination: true,
    perPage: 25,
    filters: [
      {
        key: 'class_id',
        label: 'Class',
        type: 'relation',
        placeholder: 'All classes',
        relation: { endpoint: moduleEndpoints.classes, moduleLabel: 'class' },
      },
      {
        key: 'grade_level_id',
        label: 'Grade level',
        type: 'relation',
        placeholder: 'All grades',
        relation: { endpoint: moduleEndpoints.gradeLevels, moduleLabel: 'grade level' },
      },
      {
        key: 'guardian_id',
        label: 'Guardian',
        type: 'relation',
        placeholder: 'All guardians',
        relation: { endpoint: moduleEndpoints.guardians, moduleLabel: 'guardian' },
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: recordStatusOptions,
      },
      {
        key: 'gender',
        label: 'Gender',
        type: 'select',
        placeholder: 'Any gender',
        options: genderOptions,
      },
    ],
  },
  teachers: {
    serverSearch: true,
    filterMode: 'server',
    serverPagination: true,
    perPage: 25,
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: recordStatusOptions.filter((o) => ['active', 'inactive'].includes(o.value)),
      },
    ],
  },
  guardians: {
    filterMode: 'client',
    filters: [
      {
        key: 'relationship',
        label: 'Relationship',
        type: 'select',
        placeholder: 'Any relationship',
        options: [
          { label: 'Parent', value: 'parent' },
          { label: 'Mother', value: 'mother' },
          { label: 'Father', value: 'father' },
          { label: 'Guardian', value: 'guardian' },
          { label: 'Other', value: 'other' },
        ],
      },
    ],
  },
  'academics-attendance': {
    filterMode: 'client',
    filters: [
      {
        key: 'class_id',
        label: 'Class',
        type: 'relation',
        placeholder: 'All classes',
        relation: { endpoint: moduleEndpoints.classes, moduleLabel: 'class' },
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Present', value: 'present' },
          { label: 'Absent', value: 'absent' },
          { label: 'Late', value: 'late' },
          { label: 'Excused', value: 'excused' },
        ],
      },
    ],
  },
  enrollment: {
    filterMode: 'client',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Pending', value: 'pending' },
          { label: 'Approved', value: 'approved' },
          { label: 'Rejected', value: 'rejected' },
          { label: 'Enrolled', value: 'enrolled' },
        ],
      },
    ],
  },
  'finance-payments': {
    serverSearch: true,
    filterMode: 'server',
    serverPagination: true,
    perPage: 25,
    filters: [
      {
        key: 'student_id',
        label: 'Student',
        type: 'relation',
        placeholder: 'All students',
        relation: { endpoint: moduleEndpoints.students, moduleLabel: 'student' },
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [...PAYMENT_STATUS_OPTIONS],
      },
      {
        key: 'method',
        label: 'Method',
        type: 'select',
        placeholder: 'Any method',
        options: [...PAYMENT_METHOD_OPTIONS],
      },
    ],
  },
  'finance-payroll': {
    filterMode: 'server',
    filters: [
      {
        key: 'month',
        label: 'Month',
        type: 'select',
        placeholder: 'Any month',
        options: [
          { label: 'January', value: '1' },
          { label: 'February', value: '2' },
          { label: 'March', value: '3' },
          { label: 'April', value: '4' },
          { label: 'May', value: '5' },
          { label: 'June', value: '6' },
          { label: 'July', value: '7' },
          { label: 'August', value: '8' },
          { label: 'September', value: '9' },
          { label: 'October', value: '10' },
          { label: 'November', value: '11' },
          { label: 'December', value: '12' },
        ],
      },
      {
        key: 'year',
        label: 'Year',
        type: 'select',
        placeholder: 'Any year',
        options: Array.from({ length: 6 }, (_, i) => {
          const year = String(new Date().getFullYear() - 2 + i)
          return { label: year, value: year }
        }),
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [...PAYROLL_STATUS_OPTIONS],
      },
    ],
  },
  'finance-transactions': {
    filterMode: 'server',
    filters: [
      {
        key: 'type',
        label: 'Type',
        type: 'select',
        placeholder: 'Any type',
        options: [
          { label: 'Expense', value: 'expense' },
          { label: 'Income', value: 'income' },
          { label: 'Payment', value: 'payment' },
          { label: 'Invoice / fee', value: 'fee_applied' },
          { label: 'Reversal', value: 'reversal' },
        ],
      },
      {
        key: 'category',
        label: 'Category',
        type: 'select',
        placeholder: 'Any category',
        options: [
          { label: 'Payroll', value: 'payroll' },
          { label: 'Procurement', value: 'procurement' },
          { label: 'Petty cash', value: 'petty_cash' },
          { label: 'Utilities', value: 'utilities' },
          { label: 'Travel', value: 'travel' },
          { label: 'Maintenance', value: 'maintenance' },
          { label: 'Student fees', value: 'student' },
          { label: 'Inventory', value: 'inventory' },
          { label: 'Other', value: 'other' },
        ],
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Completed', value: 'completed' },
          { label: 'Pending', value: 'pending' },
          { label: 'Reversed', value: 'reversed' },
        ],
      },
    ],
  },
  'finance-fees': {
    filterMode: 'server',
    filters: [
      {
        key: 'fee_category_id',
        label: 'Category',
        type: 'relation',
        placeholder: 'All categories',
        relation: { endpoint: moduleEndpoints.feeCategories, moduleLabel: 'fee category' },
      },
      {
        key: 'class_id',
        label: 'Class',
        type: 'relation',
        placeholder: 'All classes',
        relation: { endpoint: moduleEndpoints.classes, moduleLabel: 'class' },
      },
    ],
  },
  'finance-fee-categories': {
    filterMode: 'server',
    filters: [
      {
        key: 'is_active',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: '1' },
          { label: 'Inactive', value: '0' },
        ],
      },
    ],
  },
  'academics-terms': {
    filterMode: 'server',
    filters: [
      {
        key: 'academic_year',
        label: 'Academic year',
        type: 'select',
        placeholder: 'Any year',
        options: (() => {
          const year = new Date().getFullYear()
          return [year - 1, year, year + 1].flatMap((y) => [
            { label: `${y}-${y + 1}`, value: `${y}-${y + 1}` },
            { label: String(y), value: String(y) },
          ])
        })(),
      },
      {
        key: 'is_current',
        label: 'Current',
        type: 'select',
        placeholder: 'Any',
        options: [
          { label: 'Current term', value: '1' },
          { label: 'Not current', value: '0' },
        ],
      },
      {
        key: 'is_active',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: '1' },
          { label: 'Inactive', value: '0' },
        ],
      },
    ],
  },
  'ops-inventory': {
    filterMode: 'server',
    filters: [
      {
        key: 'type',
        label: 'Type',
        type: 'select',
        placeholder: 'Any type',
        options: [
          { label: 'Uniform', value: 'uniform' },
          { label: 'Stationery', value: 'stationery' },
          { label: 'Book', value: 'book' },
          { label: 'Equipment', value: 'equipment' },
          { label: 'Other', value: 'other' },
        ],
      },
      {
        key: 'is_active',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: '1' },
          { label: 'Inactive', value: '0' },
        ],
      },
    ],
  },
  'ops-transport': {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Inactive', value: 'inactive' },
          { label: 'Maintenance', value: 'maintenance' },
        ],
      },
    ],
  },
  'ops-transport-drivers': {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Inactive', value: 'inactive' },
        ],
      },
    ],
  },
  'ops-transport-routes': {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Inactive', value: 'inactive' },
        ],
      },
    ],
  },
  'ops-visitors': {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Checked in', value: 'checked_in' },
          { label: 'Checked out', value: 'checked_out' },
        ],
      },
    ],
  },
  'academics-streams': {
    filterMode: 'server',
    filters: [
      {
        key: 'is_active',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: '1' },
          { label: 'Inactive', value: '0' },
        ],
      },
    ],
  },
  'academics-houses': {
    filterMode: 'server',
    filters: [
      {
        key: 'is_active',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: '1' },
          { label: 'Inactive', value: '0' },
        ],
      },
    ],
  },
  'ops-library': {
    filterMode: 'server',
    filters: [
      {
        key: 'category',
        label: 'Category',
        type: 'select',
        placeholder: 'Any category',
        options: [
          { label: 'Fiction', value: 'Fiction' },
          { label: 'Non-fiction', value: 'Non-fiction' },
          { label: 'Textbook', value: 'Textbook' },
          { label: 'Reference', value: 'Reference' },
        ],
      },
    ],
  },
  'ops-events': {
    filterMode: 'server',
    filters: [
      {
        key: 'type',
        label: 'Type',
        type: 'select',
        placeholder: 'Any type',
        options: [
          { label: 'Sports day', value: 'Sports day' },
          { label: 'Meeting', value: 'Meeting' },
          { label: 'Holiday', value: 'Holiday' },
          { label: 'Exam', value: 'Exam' },
          { label: 'Other', value: 'Other' },
        ],
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Scheduled', value: 'scheduled' },
          { label: 'Ongoing', value: 'ongoing' },
          { label: 'Completed', value: 'completed' },
          { label: 'Cancelled', value: 'cancelled' },
        ],
      },
    ],
  },
  'ops-assets': {
    filterMode: 'server',
    filters: [
      {
        key: 'category',
        label: 'Category',
        type: 'select',
        placeholder: 'Any category',
        options: [
          { label: 'Furniture', value: 'Furniture' },
          { label: 'IT equipment', value: 'IT' },
          { label: 'Lab equipment', value: 'Lab' },
          { label: 'Vehicle', value: 'Vehicle' },
          { label: 'Other', value: 'Other' },
        ],
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Maintenance', value: 'maintenance' },
          { label: 'Disposed', value: 'disposed' },
        ],
      },
    ],
  },
  'ops-hostels': {
    filterMode: 'server',
    filters: [
      {
        key: 'gender',
        label: 'Gender',
        type: 'select',
        placeholder: 'Any gender',
        options: [
          { label: 'Male', value: 'male' },
          { label: 'Female', value: 'female' },
          { label: 'Mixed', value: 'mixed' },
        ],
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Inactive', value: 'inactive' },
        ],
      },
    ],
  },
  'ops-procurement': {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Draft', value: 'draft' },
          { label: 'Pending approval', value: 'pending_approval' },
          { label: 'Approved', value: 'approved' },
          { label: 'Paid', value: 'disbursed' },
          { label: 'Received', value: 'received' },
          { label: 'Rejected', value: 'rejected' },
        ],
      },
      {
        key: 'spend_type',
        label: 'Spend type',
        type: 'select',
        placeholder: 'Any type',
        options: [
          { label: 'Procurement', value: 'procurement' },
          { label: 'Petty cash', value: 'petty_cash' },
          { label: 'Utilities', value: 'utilities' },
          { label: 'Travel', value: 'travel' },
          { label: 'Maintenance', value: 'maintenance' },
          { label: 'Other', value: 'other' },
        ],
      },
    ],
  },
  'ops-procurement-vendors': {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Inactive', value: 'inactive' },
        ],
      },
    ],
  },
  compliance: {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Draft', value: 'draft' },
          { label: 'Archived', value: 'archived' },
        ],
      },
    ],
  },
  'compliance-incidents': {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Open', value: 'open' },
          { label: 'Investigating', value: 'investigating' },
          { label: 'Resolved', value: 'resolved' },
          { label: 'Closed', value: 'closed' },
        ],
      },
    ],
  },
  'compliance-consent': {
    filterMode: 'server',
    filters: [
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Archived', value: 'archived' },
        ],
      },
    ],
  },
  'finance-invoices': {
    serverSearch: true,
    filterMode: 'server',
    serverPagination: true,
    perPage: 25,
    filters: [
      {
        key: 'student_id',
        label: 'Student',
        type: 'relation',
        placeholder: 'All students',
        relation: { endpoint: moduleEndpoints.students, moduleLabel: 'student' },
      },
      {
        key: 'status',
        label: 'Status',
        type: 'select',
        placeholder: 'Any status',
        options: [...INVOICE_STATUS_OPTIONS],
      },
    ],
  },
  'settings-custom-fields': {
    filterMode: 'server',
    filters: [
      {
        key: 'entity_type',
        label: 'Entity',
        type: 'select',
        placeholder: 'All entities',
        options: [
          { label: 'Student', value: 'student' },
          { label: 'Teacher', value: 'teacher' },
          { label: 'Staff', value: 'staff' },
        ],
      },
    ],
  },
}

export function getListMeta(listKey?: string): ListPageMeta | undefined {
  if (!listKey) return undefined
  return moduleListMetaRegistry[listKey]
}
