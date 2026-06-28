import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection, genderOptions, mergeFormSections } from '@/lib/form-standards'
import {
  dateOfBirthSchema,
  formatZimPhoneHint,
  studentDobBounds,
  zimPhoneSchema,
} from '@/lib/validation'
import { guardianRelationshipOptions } from '@/modules/guardians/guardian-form'

const dobBounds = studentDobBounds()

export const enrollmentFormSchema = z.object({
  first_name: z.string().trim().min(1, 'First name is required'),
  surname: z.string().trim().min(1, 'Surname is required'),
  date_of_birth: dateOfBirthSchema,
  gender: z.enum(['male', 'female', 'other'], { required_error: 'Please select gender' }),
  phone: zimPhoneSchema,
  address: z.string().trim().min(1, 'Address is required'),
  grade_applying_for: z.string().trim().min(1, 'Grade is required'),
  academic_year: z.string().trim().min(1, 'Academic year is required'),
  guardian_first_name: z.string().trim().min(1, 'Guardian first name is required'),
  guardian_surname: z.string().trim().min(1, 'Guardian surname is required'),
  guardian_phone: zimPhoneSchema,
  guardian_relationship: z.string().trim().min(1, 'Relationship is required'),
  guardian_address: z.string().trim().min(1, 'Guardian address is required'),
  emergency_contact: z.string().trim().min(1, 'Emergency contact is required'),
  emergency_phone: zimPhoneSchema,
})

export const enrollmentFormFields: FormFieldSchema[] = mergeFormSections(
  formSection('Applicant details', [
    { name: 'first_name', label: 'First name', type: 'text', required: true },
    { name: 'surname', label: 'Surname', type: 'text', required: true },
    {
      name: 'date_of_birth',
      label: 'Date of birth',
      type: 'date',
      required: true,
      min: dobBounds.min,
      max: dobBounds.max,
      description: 'Student must be between 3 and 25 years old.',
    },
    {
      name: 'gender',
      label: 'Gender',
      type: 'select',
      required: true,
      placeholder: 'Select gender',
      options: genderOptions,
    },
    {
      name: 'phone',
      label: 'Mobile',
      type: 'phone',
      required: true,
      placeholder: '077 123 4567',
      description: `Zimbabwe mobile only. ${formatZimPhoneHint}`,
    },
    { name: 'address', label: 'Address', type: 'textarea', required: true, colSpan: 2 },
  ]),
  formSection('Application', [
    { name: 'grade_applying_for', label: 'Grade applying for', type: 'text', required: true },
    {
      name: 'academic_year',
      label: 'Academic year',
      type: 'text',
      required: true,
      placeholder: '2026',
    },
  ]),
  formSection('Guardian / parent', [
    { name: 'guardian_first_name', label: 'Guardian first name', type: 'text', required: true },
    { name: 'guardian_surname', label: 'Guardian surname', type: 'text', required: true },
    {
      name: 'guardian_relationship',
      label: 'Relationship',
      type: 'select',
      required: true,
      placeholder: 'Select relationship',
      options: guardianRelationshipOptions,
    },
    {
      name: 'guardian_phone',
      label: 'Guardian mobile',
      type: 'phone',
      required: true,
      placeholder: '077 123 4567',
    },
    { name: 'guardian_address', label: 'Guardian address', type: 'textarea', required: true, colSpan: 2 },
  ]),
  formSection('Emergency contact', [
    { name: 'emergency_contact', label: 'Contact name', type: 'text', required: true },
    {
      name: 'emergency_phone',
      label: 'Contact mobile',
      type: 'phone',
      required: true,
      placeholder: '077 123 4567',
    },
  ]),
)
