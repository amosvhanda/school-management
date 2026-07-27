import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection, mergeFormSections } from '@/lib/form-standards'
import { leaveTypeRelation } from '@/lib/form-relations'
import {
  isValidIsoDate,
} from '@/lib/validation'
import { moduleEndpoints } from '@/services'

/** Legacy hardcoded types — kept for display fallback on older leave rows. */
export const LEAVE_TYPES = [
  { label: 'Annual leave', value: 'annual' },
  { label: 'Sick leave', value: 'sick' },
  { label: 'Maternity leave', value: 'maternity' },
  { label: 'Unpaid leave', value: 'unpaid' },
  { label: 'Other', value: 'other' },
] as const

export type LeaveType = (typeof LEAVE_TYPES)[number]['value']

export const LEAVE_STATUS_OPTIONS = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
] as const

export function leaveTypeLabel(type?: string | null, leaveTypeName?: string | null): string {
  if (leaveTypeName) return leaveTypeName
  return LEAVE_TYPES.find((o) => o.value === type)?.label ?? String(type ?? '—')
}

export function leaveStatusLabel(status?: string | null): string {
  return LEAVE_STATUS_OPTIONS.find((o) => o.value === status)?.label ?? String(status ?? '—')
}

export const leaveFormSchema = z
  .object({
    teacher_id: z.string().min(1, 'Select a staff member'),
    leave_type_id: z.string().min(1, 'Select leave type'),
    start_date: z
      .string()
      .min(1, 'Start date is required')
      .refine(isValidIsoDate, 'Enter a valid start date'),
    end_date: z
      .string()
      .min(1, 'End date is required')
      .refine(isValidIsoDate, 'Enter a valid end date'),
    reason: z.string().trim().max(2000).optional().or(z.literal('')),
  })
  .superRefine((data, ctx) => {
    if (data.end_date < data.start_date) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'End date must be on or after start date',
        path: ['end_date'],
      })
    }
  })

export const leaveFormFields: FormFieldSchema[] = mergeFormSections(
  formSection('Leave request', [
    {
      name: 'teacher_id',
      label: 'Staff member',
      type: 'relation',
      required: true,
      placeholder: 'Select teacher',
      colSpan: 2,
      relation: {
        endpoint: moduleEndpoints.teachers,
        createRoute: '/people?tab=teachers&create=1',
        moduleLabel: 'teacher',
      },
    },
    {
      name: 'leave_type_id',
      label: 'Leave type',
      type: 'relation',
      required: true,
      placeholder: 'Select leave type',
      relation: leaveTypeRelation(),
      colSpan: 1,
    },
    {
      name: 'start_date',
      label: 'Start date',
      type: 'date',
      required: true,
      colSpan: 1,
    },
    {
      name: 'end_date',
      label: 'End date',
      type: 'date',
      required: true,
      colSpan: 1,
    },
    {
      name: 'reason',
      label: 'Reason',
      type: 'textarea',
      placeholder: 'Optional context for approvers and audit trail',
      colSpan: 2,
    },
  ]),
)

export interface LeaveRequestRow {
  id?: number
  teacher_id?: number
  teacher_name?: string
  employee_id?: string
  department?: string
  type?: string
  leave_type_id?: number
  leave_type_name?: string
  start_date?: string
  end_date?: string
  days?: number
  reason?: string
  status?: string
  applied_date?: string
  requested_by_name?: string
  reviewed_by?: string
  reviewed_at?: string
  review_notes?: string
  created_at?: string
  updated_at?: string
}

export function mapLeaveFormToPayload(values: Record<string, unknown>): Record<string, unknown> {
  const payload: Record<string, unknown> = {
    teacher_id: Number(values.teacher_id),
    leave_type_id: Number(values.leave_type_id),
    start_date: values.start_date,
    end_date: values.end_date,
  }
  if (values.reason) payload.reason = values.reason
  return payload
}
