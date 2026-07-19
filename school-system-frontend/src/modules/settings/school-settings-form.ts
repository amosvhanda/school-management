import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection, mergeFormSections } from '@/lib/form-standards'
import { SCHOOL_CURRENCY_OPTIONS } from '@/lib/finance-constants'
import {
  emailOptionalSchema,
  formatZimPhoneHint,
  zimPhoneOptionalSchema,
} from '@/lib/validation'

export const schoolSettingsFormSchema = z.object({
  name: z.string().trim().min(1, 'School name is required'),
  email: emailOptionalSchema,
  phone: zimPhoneOptionalSchema,
  address: z.string().trim().optional().or(z.literal('')),
  currency: z.enum(['USD', 'ZWG']),
})

export function schoolSettingsFormFields(options?: {
  currencyLocked?: boolean
}): FormFieldSchema[] {
  const locked = options?.currencyLocked === true

  return mergeFormSections(
    formSection('School profile', [
      {
        name: 'name',
        label: 'School name',
        type: 'text',
        required: true,
        placeholder: 'Mufakose 1 High School',
        colSpan: 2,
      },
      { name: 'email', label: 'Contact email', type: 'email', placeholder: 'admin@school.co.zw' },
      {
        name: 'phone',
        label: 'Contact mobile',
        type: 'phone',
        placeholder: '077 123 4567',
        description: `Zimbabwe mobile only. ${formatZimPhoneHint}`,
      },
      { name: 'address', label: 'Address', type: 'textarea', colSpan: 2 },
    ]),
    formSection('Fees currency', [
      {
        name: 'currency',
        label: 'School fees currency',
        type: 'select',
        required: true,
        options: [...SCHOOL_CURRENCY_OPTIONS],
        disabled: locked,
        colSpan: 2,
        description: locked
          ? 'Locked after payments have been recorded. All fees, invoices, store sales, and trips use this currency.'
          : 'Used for fee structures, invoices, student accounts, school store, and trip fees across the system.',
      },
    ]),
  )
}
