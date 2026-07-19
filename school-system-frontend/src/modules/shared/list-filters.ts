import { genderOptions } from '@/lib/form-standards'
import { INVOICE_STATUS_OPTIONS, PAYMENT_METHOD_OPTIONS, PAYMENT_STATUS_OPTIONS } from '@/lib/finance-constants'
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
}

export function getListMeta(listKey?: string): ListPageMeta | undefined {
  if (!listKey) return undefined
  return moduleListMetaRegistry[listKey]
}
