import { z } from 'zod'
import { moduleEndpoints } from '@/services'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection, genderOptions, mergeFormSections } from '@/lib/form-standards'
import { guardianRelationshipOptions } from '@/modules/guardians/guardian-form'
import {
  dateOfBirthSchema,
  emailOptionalSchema,
  futureDateSchema,
  studentDobBounds,
  todayIsoDate,
  zimPhoneOptionalSchema,
} from '@/lib/validation'

const dobBounds = studentDobBounds()

export const studentFormSchema = z
  .object({
    firstName: z.string().trim().min(1, 'First name is required').max(255),
    surname: z.string().trim().min(1, 'Surname is required').max(255),
    class_id: z.string().min(1, 'Class is required'),
    grade_level_id: z.string().optional().or(z.literal('')),
    dateOfBirth: dateOfBirthSchema,
    gender: z.enum(['male', 'female', 'other'], {
      required_error: 'Please select gender',
      invalid_type_error: 'Please select gender',
    }),
    phone: zimPhoneOptionalSchema,
    email: emailOptionalSchema,
    guardianMode: z.enum(['existing', 'new', 'none']).default('new'),
    guardian_id: z.string().optional().or(z.literal('')),
    guardianFirstName: z.string().trim().optional().or(z.literal('')),
    guardianSurname: z.string().trim().optional().or(z.literal('')),
    guardianPhone: zimPhoneOptionalSchema,
    guardianEmail: emailOptionalSchema,
    guardianRelationship: z.string().optional().or(z.literal('')),
  })
  .superRefine((data, ctx) => {
    if (data.guardianMode === 'none') return

    if (data.guardianMode === 'existing') {
      if (!data.guardian_id) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: 'Select a guardian from the register',
          path: ['guardian_id'],
        })
      }
      return
    }

    if (!data.guardianFirstName?.trim()) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Guardian first name is required',
        path: ['guardianFirstName'],
      })
    }
    if (!data.guardianPhone?.trim()) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Guardian mobile is required',
        path: ['guardianPhone'],
      })
    }
  })

export const studentFormFields: FormFieldSchema[] = mergeFormSections(
  formSection('Personal details', [
    {
      name: 'firstName',
      label: 'First name',
      type: 'text',
      required: true,
      placeholder: 'Tafadzwa',
    },
    {
      name: 'surname',
      label: 'Surname',
      type: 'text',
      required: true,
      placeholder: 'Gondo',
    },
    {
      name: 'dateOfBirth',
      label: 'Date of birth',
      type: 'date',
      required: true,
      min: dobBounds.min,
      max: dobBounds.max,
    },
    {
      name: 'gender',
      label: 'Gender',
      type: 'select',
      required: true,
      placeholder: 'Select gender',
      options: genderOptions,
    },
  ]),
  formSection('Enrollment', [
    {
      name: 'class_id',
      label: 'Class',
      type: 'relation',
      required: true,
      placeholder: 'Select class',
      relation: {
        endpoint: moduleEndpoints.classes,
        fallbackRowKey: 'class',
        createRoute: '/academics/setup?create=1',
        moduleLabel: 'class',
      },
    },
    {
      name: 'grade_level_id',
      label: 'Grade level',
      type: 'relation',
      placeholder: 'Select grade level',
      relation: {
        endpoint: moduleEndpoints.gradeLevels,
        createRoute: '/academics/grade-levels?create=1',
        moduleLabel: 'grade level',
      },
    },
  ]),
  formSection('Contact information', [
    {
      name: 'phone',
      label: 'Mobile',
      type: 'phone',
      placeholder: '077 123 4567',
      colSpan: 1,
    },
    {
      name: 'email',
      label: 'Email',
      type: 'email',
      placeholder: 'student@example.com',
      colSpan: 1,
    },
    {
      name: 'suburb',
      label: 'Suburb',
      type: 'text',
      placeholder: 'Mufakose',
      colSpan: 1,
    },
    {
      name: 'address',
      label: 'Address',
      type: 'textarea',
      placeholder: 'Home address',
      colSpan: 2,
    },
  ]),
  formSection('Guardian', [
    {
      name: '_guardianSection',
      label: 'Guardian',
      type: 'guardian-section',
      colSpan: 2,
    },
  ]),
)

/** Metadata for guardian fields rendered inside GuardianLinkSection (used by mappers). */
export const studentGuardianMetaFields: FormFieldSchema[] = [
  {
    name: 'guardian_id',
    label: 'Guardian',
    type: 'relation',
    rowKey: 'guardian.id',
    relation: {
      endpoint: moduleEndpoints.guardians,
      createRoute: '/guardians?create=1',
      moduleLabel: 'guardian',
    },
  },
  {
    name: 'guardianFirstName',
    label: 'First name',
    type: 'text',
    rowKey: 'guardian.first_name',
    payloadPath: 'guardian.firstName',
  },
  {
    name: 'guardianSurname',
    label: 'Surname',
    type: 'text',
    rowKey: 'guardian.last_name',
    payloadPath: 'guardian.surname',
  },
  {
    name: 'guardianRelationship',
    label: 'Relationship',
    type: 'select',
    rowKey: 'guardian.relationship',
    payloadPath: 'guardian.relationship',
    options: guardianRelationshipOptions,
  },
  {
    name: 'guardianPhone',
    label: 'Mobile',
    type: 'phone',
    rowKey: 'guardian.phone',
    payloadPath: 'guardian.phone',
  },
  {
    name: 'guardianEmail',
    label: 'Email',
    type: 'email',
    rowKey: 'guardian.email',
    payloadPath: 'guardian.email',
  },
]

export const studentInvoiceSchema = z.object({
  amount: z.coerce.number({ invalid_type_error: 'Amount is required' }).min(0.01, 'Amount must be greater than zero'),
  description: z.string().trim().min(1, 'Description is required').max(500),
  dueDate: futureDateSchema,
})

export const studentInvoiceFields: FormFieldSchema[] = [
  {
    name: 'amount',
    label: 'Amount',
    type: 'number',
    section: 'Invoice details',
    required: true,
    placeholder: '150.00',
    colSpan: 2,
  },
  {
    name: 'description',
    label: 'Description',
    type: 'textarea',
    section: 'Invoice details',
    required: true,
    placeholder: 'Term 1 tuition fees',
    colSpan: 2,
  },
  {
    name: 'dueDate',
    label: 'Due date',
    type: 'date',
    section: 'Invoice details',
    required: true,
    min: todayIsoDate(),
    colSpan: 2,
  },
]
