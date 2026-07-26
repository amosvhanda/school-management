import { endpoints } from '@/services/endpoints'
import type { ActionPromptForm } from '@/modules/shared/action-prompt-forms'
import {
  inventoryRestockPromptForm,
  payrollGeneratePromptForm,
  payrollProcessPromptForm,
  receiveGoodsPromptForm,
  schoolTripEnrollPromptForm,
  certificateIssuePromptForm,
  spendDisbursePromptForm,
} from '@/modules/shared/action-prompt-forms'

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
  /** Download response as a file blob (e.g. HTML certificate) */
  downloadBlob?: boolean
  downloadFilename?: (row: Record<string, unknown>) => string
  /** Prompt for reason before destructive API action */
  confirmReason?: boolean
  /** Opens a validated FormSheet before calling the API */
  promptForm?: ActionPromptForm
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
      body: { is_current: true, is_active: true },
      successMessage: 'Term set as current — other terms are now inactive',
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
      when: (row) => {
        const status = String(row.status ?? '').toLowerCase()
        return status === 'pending' || status === 'partial'
      },
      promptForm: payrollProcessPromptForm,
      successMessage: 'Payroll payment recorded',
    },
  ],
  'ops-inventory': [
    {
      label: 'Restock',
      method: 'post',
      path: (id) => endpoints.inventory.restock(id),
      variant: 'outline',
      promptForm: inventoryRestockPromptForm,
      successMessage: 'Stock updated',
    },
  ],
  'ops-procurement': [
    {
      label: 'Submit',
      method: 'post',
      path: (id) => endpoints.procurement.submit(id),
      when: (row) => String(row.status ?? '').toLowerCase() === 'draft',
      successMessage: 'Submitted for approval',
    },
    {
      label: 'Record payment',
      method: 'post',
      path: (id) => endpoints.procurement.disburse(id),
      when: (row) => String(row.status ?? '').toLowerCase() === 'approved',
      promptForm: spendDisbursePromptForm,
      successMessage: 'Payment recorded on the ledger',
    },
    {
      label: 'Receive goods',
      method: 'post',
      path: () => endpoints.procurement.goodsReceipts,
      when: (row) => {
        const status = String(row.status ?? '').toLowerCase()
        const spendType = String(row.spend_type ?? 'procurement').toLowerCase()
        return (status === 'approved' || status === 'disbursed') && spendType === 'procurement'
      },
      promptForm: receiveGoodsPromptForm,
      body: (row) => ({
        requisition_id: row.id,
      }),
      successMessage: 'Goods receipt recorded',
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
  'ops-school-trips': [
    {
      label: 'Enroll student',
      method: 'post',
      path: (id) => endpoints.schoolTrips.enroll(id),
      when: (row) =>
        row.is_active !== false
        && row.is_active !== 0
        && row.open_for_registration !== false
        && row.open_for_registration !== 0,
      promptForm: schoolTripEnrollPromptForm,
      successMessage: 'Student enrolled for the trip',
    },
  ],
  'academics-certificate-templates': [
    {
      label: 'Issue certificate',
      method: 'post',
      path: (id) => endpoints.certificateTemplates.issue(id),
      when: (row) => row.is_active !== false && row.is_active !== 0,
      promptForm: certificateIssuePromptForm,
      successMessage: 'Certificate issued',
    },
  ],
  'academics-school-certificates': [
    {
      label: 'Download',
      method: 'post',
      path: (id) => endpoints.schoolCertificates.download(id),
      variant: 'outline',
      downloadBlob: true,
      downloadFilename: (row) => {
        const code = String(row.verification_code ?? row.id ?? 'certificate')
        return `certificate_${code}.html`
      },
      successMessage: 'Certificate downloaded',
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
      promptForm: payrollGeneratePromptForm,
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
