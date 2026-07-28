import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection } from '@/lib/form-standards'
import { emailRequiredSchema } from '@/lib/validation'

export const userRoleOptions = [
  { label: 'Admin', value: 'admin' },
  { label: 'School Admin', value: 'school_admin' },
  { label: 'Teacher', value: 'teacher' },
  { label: 'Finance', value: 'finance' },
  { label: 'Accounts', value: 'accounts' },
  { label: 'Examination Officer', value: 'examination_officer' },
  { label: 'Receptionist', value: 'receptionist' },
  { label: 'Librarian', value: 'librarian' },
  { label: 'School Nurse', value: 'nurse' },
  { label: 'Transport Manager', value: 'transport_manager' },
  { label: 'Hostel Manager', value: 'hostel_manager' },
  { label: 'Parent', value: 'parent' },
  { label: 'Student', value: 'student' },
]

export const userFormSchema = z.object({
  name: z.string().trim().min(1, 'Name is required'),
  email: emailRequiredSchema,
  role: z.string().min(1, 'Role is required'),
  password: z.string().optional(),
})

export const userFormFields: FormFieldSchema[] = formSection('Account details', [
  { name: 'name', label: 'Full name', type: 'text', required: true, placeholder: 'Jane Doe' },
  { name: 'email', label: 'Email', type: 'email', required: true, placeholder: 'jane@school.co.zw' },
  {
    name: 'role',
    label: 'Role',
    type: 'select',
    required: true,
    placeholder: 'Select role',
    options: userRoleOptions,
    colSpan: 2,
  },
  {
    name: 'password',
    label: 'Password',
    type: 'password',
    placeholder: 'Leave blank to use default password123',
    description: 'Only required when inviting a new user.',
    colSpan: 2,
  },
])
