import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { departmentRelation, subjectRelation } from '@/lib/form-relations'
import { formSection, mergeFormSections } from '@/lib/form-standards'
import {
  emailRequiredSchema,
  formatZimPhoneHint,
  optionalDateSchema,
  zimPhoneOptionalSchema,
} from '@/lib/validation'

export const teacherFormSchema = z.object({
  firstName: z.string().trim().min(1, 'First name is required').max(255),
  surname: z.string().trim().min(1, 'Surname is required').max(255),
  email: emailRequiredSchema,
  phone: zimPhoneOptionalSchema,
  address: z.string().trim().optional().or(z.literal('')),
  subject_id: z.string().optional().or(z.literal('')),
  department_id: z.string().optional().or(z.literal('')),
  qualification: z.string().trim().optional().or(z.literal('')),
  joiningDate: optionalDateSchema,
})

export const teacherFormFields: FormFieldSchema[] = mergeFormSections(
  formSection('Personal details', [
    {
      name: 'firstName',
      label: 'First name',
      type: 'text',
      required: true,
      placeholder: 'Tendai',
    },
    {
      name: 'surname',
      label: 'Surname',
      type: 'text',
      required: true,
      placeholder: 'Moyo',
    },
  ]),
  formSection('Contact information', [
    {
      name: 'email',
      label: 'Email',
      type: 'email',
      required: true,
      placeholder: 'teacher@school.co.zw',
      colSpan: 1,
    },
    {
      name: 'phone',
      label: 'Mobile',
      type: 'phone',
      placeholder: '077 123 4567',
      description: `Zimbabwe mobile only. ${formatZimPhoneHint}`,
      colSpan: 1,
    },
    {
      name: 'address',
      label: 'Address',
      type: 'textarea',
      placeholder: 'Optional home or postal address',
      colSpan: 2,
    },
  ]),
  formSection('Employment', [
    {
      name: 'subject_id',
      label: 'Primary subject',
      type: 'relation',
      placeholder: 'Select subject',
      relation: subjectRelation(),
      colSpan: 1,
    },
    {
      name: 'department_id',
      label: 'Department',
      type: 'relation',
      placeholder: 'Select department',
      relation: departmentRelation(),
      colSpan: 1,
    },
    {
      name: 'qualification',
      label: 'Qualification',
      type: 'text',
      placeholder: 'BSc Education',
      colSpan: 1,
    },
    {
      name: 'joiningDate',
      label: 'Joining date',
      type: 'date',
      colSpan: 1,
    },
  ]),
)
