import { endpoints } from '@/services/endpoints'

export type RowActionMethod = 'post' | 'put' | 'patch' | 'delete'

export interface RowActionConfig {
  label: string
  method: RowActionMethod
  path: (id: string | number) => string
  variant?: 'default' | 'outline' | 'destructive' | 'secondary'
  when?: (row: Record<string, unknown>) => boolean
  body?:
    | Record<string, unknown>
    | ((row: Record<string, unknown>) => Record<string, unknown> | null)
  successMessage?: string
  /** Opens payment receipt sheet instead of calling API directly */
  openReceipt?: boolean
  /** Prompt for reason before destructive API action */
  confirmReason?: boolean
}

export const moduleActionsRegistry: Record<string, RowActionConfig[]> = {
  workflows: [
    {
      label: 'Approve',
      method: 'post',
      path: (id) => endpoints.workflows.approve(id),
      successMessage: 'Workflow approved',
    },
    {
      label: 'Reject',
      method: 'post',
      path: (id) => endpoints.workflows.reject(id),
      variant: 'outline',
      when: (row) => String(row.status ?? '').toLowerCase() === 'pending',
      confirmReason: true,
      successMessage: 'Workflow rejected',
    },
  ],
  'hr-leave': [
    {
      label: 'Approve',
      method: 'post',
      path: (id) => endpoints.leaveRequests.approve(id),
      when: (row) => String(row.status ?? '').toLowerCase() === 'pending',
      successMessage: 'Leave approved',
    },
    {
      label: 'Reject',
      method: 'post',
      path: (id) => endpoints.leaveRequests.reject(id),
      variant: 'outline',
      when: (row) => String(row.status ?? '').toLowerCase() === 'pending',
      confirmReason: true,
      successMessage: 'Leave rejected',
    },
  ],
  'academics-exams': [
    {
      label: 'Publish',
      method: 'post',
      path: (id) => endpoints.exams.publish(id),
      when: (row) => String(row.status ?? '').toLowerCase() !== 'published',
      successMessage: 'Exam published',
    },
    {
      label: 'Approve results',
      method: 'post',
      path: (id) => endpoints.exams.approveResults(id),
      variant: 'outline',
      successMessage: 'Results approved',
    },
  ],
  'academics-terms': [
    {
      label: 'Set as current',
      method: 'put',
      path: (id) => endpoints.terms.detail(id),
      when: (row) => row.is_current !== true && row.is_current !== 1,
      body: { is_current: true },
      successMessage: 'Current term updated',
    },
  ],
  'finance-payments': [
    {
      label: 'Receipt',
      method: 'post',
      path: (id) => endpoints.payments.receipt(id),
      variant: 'outline',
      when: (row) => String(row.status ?? '').toLowerCase() === 'completed',
      openReceipt: true,
    },
    {
      label: 'Reverse',
      method: 'post',
      path: (id) => endpoints.payments.reverse(id),
      variant: 'destructive',
      when: (row) => String(row.status ?? '').toLowerCase() === 'completed',
      confirmReason: true,
      successMessage: 'Payment reversed',
    },
  ],
  'finance-payroll': [
    {
      label: 'Process',
      method: 'post',
      path: (id) => endpoints.payroll.process(id),
      when: (row) => String(row.status ?? '').toLowerCase() === 'pending',
      successMessage: 'Payroll processed',
    },
  ],
  'ops-inventory': [
    {
      label: 'Restock',
      method: 'post',
      path: (id) => endpoints.inventory.restock(id),
      variant: 'outline',
      body: () => {
        const raw = window.prompt('Restock quantity', '10')
        if (raw == null) return null
        const quantity = Math.max(1, Number(raw) || 1)
        return { quantity }
      },
      successMessage: 'Stock updated',
    },
  ],
  'ops-visitors': [
    {
      label: 'Check out',
      method: 'post',
      path: (id) => endpoints.visitors.checkOut(id),
      when: (row) => String(row.status ?? '').toLowerCase() === 'checked_in',
      successMessage: 'Visitor checked out',
    },
  ],
  'ops-assets': [
    {
      label: 'Dispose',
      method: 'post',
      path: (id) => endpoints.assets.dispose(id),
      variant: 'destructive',
      confirmReason: true,
      body: () => ({
        disposed_at: new Date().toISOString().slice(0, 10),
        reason: 'Disposed from admin panel',
      }),
      successMessage: 'Asset disposal submitted',
    },
  ],
  teachers: [
    {
      label: 'Deactivate',
      method: 'patch',
      path: (id) => endpoints.teachers.status(id),
      variant: 'outline',
      body: { status: 'inactive' },
      when: (row) => String(row.status ?? '').toLowerCase() === 'active',
      successMessage: 'Teacher deactivated',
    },
    {
      label: 'Activate',
      method: 'patch',
      path: (id) => endpoints.teachers.status(id),
      when: (row) => String(row.status ?? '').toLowerCase() !== 'active',
      body: { status: 'active' },
      successMessage: 'Teacher activated',
    },
  ],
}

export const moduleToolbarActionsRegistry: Record<string, RowActionConfig[]> = {
  'finance-payroll': [
    {
      label: 'Generate payroll',
      method: 'post',
      path: () => endpoints.payroll.generate,
      body: {
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear(),
      },
      successMessage: 'Payroll generated for active staff',
    },
  ],
  'academics-timetable': [
    {
      label: 'Auto-generate',
      method: 'post',
      path: () => endpoints.timetable.generateBulk,
      body: { all_classes: true },
      successMessage: 'Timetable generated for all classes',
    },
  ],
}
