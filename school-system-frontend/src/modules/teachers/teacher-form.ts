import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { departmentRelation, designationRelation, subjectRelation } from '@/lib/form-relations'
import { formSection, mergeFormSections } from '@/lib/form-standards'
import { PAYROLL_PAYMENT_METHOD_OPTIONS, SCHOOL_CURRENCY_OPTIONS } from '@/lib/finance-constants'
import {
  emailRequiredSchema,
  formatZimPhoneHint,
  isValidIsoDate,
  zimPhoneOptionalSchema,
} from '@/lib/validation'

export const TEACHER_STATUS_OPTIONS = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'On leave', value: 'on_leave' },
] as const

export const TEACHER_EMPLOYMENT_TYPE_OPTIONS = [
  { label: 'Full time', value: 'full_time' },
  { label: 'Part time', value: 'part_time' },
] as const

const optionalMoney = z.preprocess(
  (val) => (val === '' || val === null || val === undefined ? undefined : val),
  z.coerce.number().min(0).optional(),
)

export const teacherFormSchema = z
  .object({
    firstName: z.string().trim().min(1, 'First name is required').max(255),
    surname: z.string().trim().min(1, 'Surname is required').max(255),
    email: emailRequiredSchema,
    phone: zimPhoneOptionalSchema,
    address: z.string().trim().optional().or(z.literal('')),
    subject_id: z.string().optional().or(z.literal('')),
    department_id: z.string().optional().or(z.literal('')),
    designation_id: z.string().optional().or(z.literal('')),
    qualification: z.string().trim().optional().or(z.literal('')),
    joiningDate: z
      .string()
      .optional()
      .or(z.literal(''))
      .refine((val) => !val || isValidIsoDate(val), 'Enter a valid date'),
    status: z.enum(['active', 'inactive', 'on_leave']).default('active'),
    employment_type: z.enum(['full_time', 'part_time']).default('full_time'),
    base_salary: optionalMoney,
    period_rate: optionalMoney,
    salary_currency: z.enum(['USD', 'ZWG']).default('USD'),
    allowances_total: optionalMoney,
    deductions_total: optionalMoney,
    bank_name: z.string().trim().optional().or(z.literal('')),
    bank_account_number: z.string().trim().optional().or(z.literal('')),
    payment_method: z
      .enum(['bank_transfer', 'cash', 'ecocash', 'onemoney', 'zipit', 'swipe'])
      .optional()
      .or(z.literal('')),
  })
  .superRefine((data, ctx) => {
    if (data.employment_type === 'part_time' && (data.period_rate == null || data.period_rate <= 0)) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Period rate is required for part-time staff',
        path: ['period_rate'],
      })
    }
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
      description: 'Used for staff login and school communications.',
    },
    {
      name: 'phone',
      label: 'Mobile',
      type: 'phone',
      placeholder: '077 123 4567',
      description: `Zimbabwe mobile only. ${formatZimPhoneHint}`,
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
    },
    {
      name: 'department_id',
      label: 'Department',
      type: 'relation',
      placeholder: 'Select department',
      relation: departmentRelation(),
    },
    {
      name: 'designation_id',
      label: 'Designation',
      type: 'relation',
      placeholder: 'Select job title',
      relation: designationRelation(),
      description: 'Job title from HR designations (Head Teacher, Teacher, Clerk…).',
    },
    {
      name: 'qualification',
      label: 'Qualification',
      type: 'text',
      placeholder: 'BSc Education',
    },
    {
      name: 'joiningDate',
      label: 'Joining date',
      type: 'date',
    },
    {
      name: 'status',
      label: 'Status',
      type: 'select',
      required: true,
      options: [...TEACHER_STATUS_OPTIONS],
    },
    {
      name: 'employment_type',
      label: 'Employment type',
      type: 'select',
      required: true,
      options: [...TEACHER_EMPLOYMENT_TYPE_OPTIONS],
      description: 'Part-time staff need a period rate for payroll.',
    },
  ]),
  formSection('Payroll details', [
    {
      name: 'base_salary',
      label: 'Base salary',
      type: 'number',
      placeholder: '0.00',
      description: 'Monthly base for full-time staff. Required before payroll can run.',
      min: '0',
    },
    {
      name: 'period_rate',
      label: 'Period rate',
      type: 'number',
      placeholder: '0.00',
      description: 'Pay per period for part-time staff.',
      min: '0',
    },
    {
      name: 'salary_currency',
      label: 'Salary currency',
      type: 'select',
      required: true,
      options: [...SCHOOL_CURRENCY_OPTIONS],
    },
    {
      name: 'allowances_total',
      label: 'Monthly allowances',
      type: 'number',
      placeholder: '0.00',
      min: '0',
    },
    {
      name: 'deductions_total',
      label: 'Monthly deductions',
      type: 'number',
      placeholder: '0.00',
      min: '0',
    },
    {
      name: 'payment_method',
      label: 'Preferred payout method',
      type: 'select',
      options: [...PAYROLL_PAYMENT_METHOD_OPTIONS],
    },
    {
      name: 'bank_name',
      label: 'Bank name',
      type: 'text',
      placeholder: 'CBZ / Stanbic / …',
    },
    {
      name: 'bank_account_number',
      label: 'Bank account number',
      type: 'text',
      placeholder: 'Account number',
    },
  ]),
)

export function teacherCreateDefaults(currency: 'USD' | 'ZWG' = 'USD'): Record<string, unknown> {
  return {
    status: 'active',
    employment_type: 'full_time',
    salary_currency: currency,
    payment_method: 'bank_transfer',
  }
}
