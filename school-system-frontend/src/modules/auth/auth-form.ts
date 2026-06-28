import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection } from '@/lib/form-standards'
import { emailRequiredSchema } from '@/lib/validation'

export const loginFormSchema = z.object({
  email: emailRequiredSchema,
  password: z.string().min(1, 'Password is required'),
})

export const loginFormFields: FormFieldSchema[] = formSection('Sign in', [
  {
    name: 'email',
    label: 'Email',
    type: 'email',
    required: true,
    placeholder: 'admin@school.co.zw',
    colSpan: 2,
  },
  {
    name: 'password',
    label: 'Password',
    type: 'password',
    required: true,
    colSpan: 2,
  },
])

export const licenseFormSchema = z.object({
  licenseKey: z.string().trim().min(1, 'License key is required'),
})

export const licenseFormFields: FormFieldSchema[] = formSection('License activation', [
  {
    name: 'licenseKey',
    label: 'License key',
    type: 'text',
    required: true,
    placeholder: 'SKERP-...',
    colSpan: 2,
  },
])
