import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection } from '@/lib/form-standards'
import { isValidIsoDate } from '@/lib/validation'

export const ANNOUNCEMENT_TYPES = [
  { label: 'Information', value: 'info' },
  { label: 'Important', value: 'important' },
  { label: 'Warning', value: 'warning' },
  { label: 'Success / celebration', value: 'success' },
] as const

export const ANNOUNCEMENT_AUDIENCES = [
  { label: 'Everyone', value: 'all' },
  { label: 'Parents', value: 'parents' },
  { label: 'Students', value: 'students' },
  { label: 'Teachers', value: 'teachers' },
  { label: 'Staff', value: 'staff' },
] as const

export const announcementFormSchema = z.object({
  title: z.string().trim().min(1, 'Title is required').max(255),
  message: z.string().trim().min(1, 'Message is required'),
  type: z.enum(['info', 'important', 'warning', 'success']),
  target_audience: z.enum(['all', 'students', 'parents', 'teachers', 'staff']),
  date: z
    .string()
    .min(1, 'Publish date is required')
    .refine(isValidIsoDate, 'Enter a valid date'),
  is_active: z.boolean().optional(),
})

export const announcementFormFields: FormFieldSchema[] = [
  ...formSection('Announcement', [
    { name: 'title', label: 'Title', type: 'text', required: true, colSpan: 2, placeholder: 'Sports day next Friday' },
    {
      name: 'type',
      label: 'Type',
      type: 'select',
      required: true,
      options: [...ANNOUNCEMENT_TYPES],
    },
    {
      name: 'target_audience',
      label: 'Audience',
      type: 'select',
      required: true,
      options: [...ANNOUNCEMENT_AUDIENCES],
    },
    { name: 'date', label: 'Publish date', type: 'date', required: true },
    { name: 'message', label: 'Message', type: 'textarea', required: true, colSpan: 2, placeholder: 'Write the announcement parents and staff will read…' },
    { name: 'is_active', label: 'Visible to audience', type: 'checkbox', description: 'Uncheck to hide without deleting' },
  ]),
]

export function announcementCreateDefaults(): Record<string, unknown> {
  return {
    title: '',
    message: '',
    type: 'info',
    target_audience: 'all',
    date: new Date().toISOString().slice(0, 10),
    is_active: true,
  }
}

export function mapAnnouncementRowToFormValues(row: Record<string, unknown>): Record<string, unknown> {
  return {
    title: String(row.title ?? ''),
    message: String(row.message ?? ''),
    type: String(row.type ?? 'info'),
    target_audience: String(row.target_audience ?? 'all'),
    date: String(row.date ?? '').slice(0, 10),
    is_active: row.is_active !== false,
  }
}

export function mapAnnouncementFormToPayload(values: Record<string, unknown>): Record<string, unknown> {
  return {
    title: String(values.title ?? '').trim(),
    message: String(values.message ?? '').trim(),
    type: values.type ?? 'info',
    target_audience: values.target_audience ?? 'all',
    date: values.date,
    is_active: values.is_active !== false,
  }
}

export function audienceLabel(value: string | undefined): string {
  return ANNOUNCEMENT_AUDIENCES.find((a) => a.value === value)?.label ?? value ?? '—'
}

export function typeLabel(value: string | undefined): string {
  return ANNOUNCEMENT_TYPES.find((t) => t.value === value)?.label ?? value ?? '—'
}
