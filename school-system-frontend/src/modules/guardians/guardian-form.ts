import { z } from 'zod'
import { moduleEndpoints } from '@/services'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection, mergeFormSections } from '@/lib/form-standards'
import {
  emailOptionalSchema,
  formatZimPhoneHint,
  zimPhoneSchema,
} from '@/lib/validation'

export const guardianRelationshipOptions = [
  { label: 'Parent', value: 'parent' },
  { label: 'Mother', value: 'mother' },
  { label: 'Father', value: 'father' },
  { label: 'Guardian', value: 'guardian' },
  { label: 'Other', value: 'other' },
]

export const guardianFormSchema = z.object({
  first_name: z.string().trim().min(1, 'First name is required').max(255),
  last_name: z.string().trim().min(1, 'Surname is required').max(255),
  phone: zimPhoneSchema,
  email: emailOptionalSchema,
  relationship: z.string().optional().or(z.literal('')),
  student_id: z.string().optional().or(z.literal('')),
})

export const guardianFormFields: FormFieldSchema[] = mergeFormSections(
  formSection('Guardian details', [
    {
      name: 'first_name',
      label: 'First name',
      type: 'text',
      required: true,
    },
    {
      name: 'last_name',
      label: 'Surname',
      type: 'text',
      required: true,
    },
    {
      name: 'phone',
      label: 'Mobile',
      type: 'phone',
      required: true,
      placeholder: '077 123 4567',
      description: `Zimbabwe mobile only. ${formatZimPhoneHint}`,
    },
    {
      name: 'email',
      label: 'Email',
      type: 'email',
      placeholder: 'parent@example.com',
      description: 'Used for parent portal access when provided.',
    },
    {
      name: 'relationship',
      label: 'Relationship',
      type: 'select',
      placeholder: 'Select relationship',
      options: guardianRelationshipOptions,
      colSpan: 2,
    },
  ]),
  formSection('Student link', [
    {
      name: 'student_id',
      label: 'Link to student',
      type: 'relation',
      placeholder: 'Select student',
      description: 'Links this guardian to the student record in the guardian register.',
      colSpan: 2,
      relation: {
        endpoint: moduleEndpoints.students,
        createRoute: '/people?tab=students&create=1',
        moduleLabel: 'student',
      },
    },
  ]),
)
