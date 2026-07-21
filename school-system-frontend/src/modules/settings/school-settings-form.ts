import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection, mergeFormSections } from '@/lib/form-standards'
import { SCHOOL_CURRENCY_OPTIONS } from '@/lib/finance-constants'
import {
  emailOptionalSchema,
  formatZimPhoneHint,
  zimPhoneOptionalSchema,
} from '@/lib/validation'

const ZIMBABWE_CITIES = [
  { label: 'Harare', value: 'Harare' },
  { label: 'Bulawayo', value: 'Bulawayo' },
  { label: 'Chitungwiza', value: 'Chitungwiza' },
  { label: 'Mutare', value: 'Mutare' },
  { label: 'Gweru', value: 'Gweru' },
  { label: 'Kwekwe', value: 'Kwekwe' },
  { label: 'Kadoma', value: 'Kadoma' },
  { label: 'Masvingo', value: 'Masvingo' },
  { label: 'Chinhoyi', value: 'Chinhoyi' },
  { label: 'Marondera', value: 'Marondera' },
  { label: 'Norton', value: 'Norton' },
  { label: 'Bindura', value: 'Bindura' },
  { label: 'Zvishavane', value: 'Zvishavane' },
  { label: 'Victoria Falls', value: 'Victoria Falls' },
  { label: 'Hwange', value: 'Hwange' },
  { label: 'Beitbridge', value: 'Beitbridge' },
  { label: 'Kariba', value: 'Kariba' },
  { label: 'Rusape', value: 'Rusape' },
  { label: 'Chegutu', value: 'Chegutu' },
  { label: 'Other', value: 'Other' },
]

const HARARE_SUBURBS = [
  { label: 'Mufakose', value: 'Mufakose' },
  { label: 'Highfield', value: 'Highfield' },
  { label: 'Glen Norah', value: 'Glen Norah' },
  { label: 'Glen View', value: 'Glen View' },
  { label: 'Mbare', value: 'Mbare' },
  { label: 'Warren Park', value: 'Warren Park' },
  { label: 'Kuwadzana', value: 'Kuwadzana' },
  { label: 'Budiriro', value: 'Budiriro' },
  { label: 'Dzivaresekwa', value: 'Dzivaresekwa' },
  { label: 'Kambuzuma', value: 'Kambuzuma' },
  { label: 'Tafara', value: 'Tafara' },
  { label: 'Mabvuku', value: 'Mabvuku' },
  { label: 'Hatfield', value: 'Hatfield' },
  { label: 'Avondale', value: 'Avondale' },
  { label: 'Mount Pleasant', value: 'Mount Pleasant' },
  { label: 'Borrowdale', value: 'Borrowdale' },
  { label: 'Greendale', value: 'Greendale' },
  { label: 'Highlands', value: 'Highlands' },
  { label: 'Eastlea', value: 'Eastlea' },
  { label: 'Arcadia', value: 'Arcadia' },
  { label: 'Chitungwiza', value: 'Chitungwiza' },
  { label: 'Epworth', value: 'Epworth' },
  { label: 'Ruwa', value: 'Ruwa' },
  { label: 'Other', value: 'Other' },
]

const TIMEZONE_OPTIONS = [
  { label: 'Africa/Harare (CAT)', value: 'Africa/Harare' },
  { label: 'Africa/Johannesburg (SAST)', value: 'Africa/Johannesburg' },
  { label: 'Africa/Lusaka (CAT)', value: 'Africa/Lusaka' },
  { label: 'Africa/Maputo (CAT)', value: 'Africa/Maputo' },
  { label: 'UTC', value: 'UTC' },
]

export const schoolSettingsFormSchema = z.object({
  name: z.string().trim().min(1, 'School name is required'),
  principal_name: z.string().trim().optional().or(z.literal('')),
  email: emailOptionalSchema,
  phone: zimPhoneOptionalSchema,
  website: z.string().trim().url('Enter a valid URL').optional().or(z.literal('')),
  year_founded: z.coerce
    .number()
    .int()
    .min(1800, 'Year must be after 1800')
    .max(new Date().getFullYear(), 'Year cannot be in the future')
    .optional()
    .or(z.literal('')),
  suburb: z.string().optional().or(z.literal('')),
  city: z.string().optional().or(z.literal('')),
  student_capacity: z.coerce
    .number()
    .int()
    .min(1, 'Capacity must be at least 1')
    .optional()
    .or(z.literal('')),
  timezone: z.string().optional().or(z.literal('')),
  currency: z.enum(['USD', 'ZWG']),
  motto: z.string().trim().max(500).optional().or(z.literal('')),
  address: z.string().trim().optional().or(z.literal('')),
})

export function schoolSettingsFormFields(options?: {
  currencyLocked?: boolean
}): FormFieldSchema[] {
  const locked = options?.currencyLocked === true

  return mergeFormSections(
    formSection('School Profile', [
      {
        name: 'name',
        label: 'School Name',
        type: 'text',
        required: true,
        placeholder: 'e.g., Mufakose 1 High School',
      },
      {
        name: 'principal_name',
        label: 'Principal Name',
        type: 'text',
        placeholder: 'e.g., Mr. James Mutamba',
      },
      {
        name: 'email',
        label: 'School Email',
        type: 'email',
        required: true,
        placeholder: 'info@yourschool.edu.zw',
      },
      {
        name: 'phone',
        label: 'School Phone',
        type: 'phone',
        required: true,
        placeholder: '+263 4 123 4567',
        description: `Zimbabwe mobile only. ${formatZimPhoneHint}`,
      },
      {
        name: 'website',
        label: 'Website',
        type: 'text',
        placeholder: 'www.yourschool.edu.zw',
      },
      {
        name: 'year_founded',
        label: 'Year Founded',
        type: 'number',
        placeholder: 'e.g., 1985',
      },
      {
        name: 'suburb',
        label: 'Suburb',
        type: 'select',
        placeholder: 'Select suburb',
        options: HARARE_SUBURBS,
      },
      {
        name: 'city',
        label: 'City',
        type: 'select',
        placeholder: 'Select city',
        options: ZIMBABWE_CITIES,
      },
      {
        name: 'student_capacity',
        label: 'Student Capacity',
        type: 'number',
        placeholder: '1000',
      },
      {
        name: 'timezone',
        label: 'Timezone',
        type: 'select',
        placeholder: 'Select timezone',
        options: TIMEZONE_OPTIONS,
      },
      {
        name: 'currency',
        label: 'Primary Currency',
        type: 'select',
        required: true,
        options: [...SCHOOL_CURRENCY_OPTIONS],
        disabled: locked,
        description: locked
          ? 'Locked after payments have been recorded.'
          : 'Used for fees, invoices, store sales, and trips.',
      },
      {
        name: 'motto',
        label: 'School Motto',
        type: 'text',
        placeholder: 'e.g., Excellence Through Education',
      },
      {
        name: 'address',
        label: 'Physical Address',
        type: 'textarea',
        required: true,
        colSpan: 2,
        placeholder: 'Full physical address of the school',
      },
    ]),
  )
}
