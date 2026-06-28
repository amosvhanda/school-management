export const WORKFLOW_STATUS_OPTIONS = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
] as const

export const WORKFLOW_MODULE_OPTIONS = [
  { label: 'Procurement', value: 'procurement' },
  { label: 'Human resources', value: 'hr' },
  { label: 'Finance', value: 'finance' },
  { label: 'Examinations', value: 'examination' },
  { label: 'Assets', value: 'assets' },
] as const

export interface WorkflowStep {
  step_order?: number
  name?: string
  approver_role?: string | null
}

export interface WorkflowApprovalRow {
  id?: number
  step_order?: number
  action?: string
  comments?: string | null
  acted_at?: string
  approver_name?: string | null
}

export interface WorkflowRow {
  id?: number
  status?: string
  current_step_order?: number
  current_step_name?: string | null
  total_steps?: number | null
  workflow_code?: string
  workflow_name?: string
  module?: string
  subject_type?: string
  subject_id?: number
  subject_label?: string
  metadata?: Record<string, unknown> | null
  initiated_by_name?: string | null
  initiator?: { id?: number; name?: string; email?: string }
  definition?: {
    id?: number
    code?: string
    name?: string
    module?: string
    steps?: WorkflowStep[]
  }
  approvals?: WorkflowApprovalRow[]
  created_at?: string
  completed_at?: string | null
}

export function workflowStatusLabel(status?: string | null): string {
  return WORKFLOW_STATUS_OPTIONS.find((o) => o.value === status)?.label ?? String(status ?? '—')
}

export function workflowModuleLabel(module?: string | null): string {
  return WORKFLOW_MODULE_OPTIONS.find((o) => o.value === module)?.label
    ?? String(module ?? '—').replace(/_/g, ' ')
}

export function workflowProgress(row: WorkflowRow): string {
  if (!row.total_steps) return row.current_step_name ?? '—'
  return `Step ${row.current_step_order ?? 1} of ${row.total_steps}${row.current_step_name ? ` · ${row.current_step_name}` : ''}`
}
