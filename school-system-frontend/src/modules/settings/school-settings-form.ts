import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection, mergeFormSections } from '@/lib/form-standards'
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
})

export const schoolSettingsFormFields: FormFieldSchema[] = mergeFormSections(
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
)
