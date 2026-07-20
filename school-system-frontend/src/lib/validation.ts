import { z } from 'zod'

/** Strip spaces, dashes, and parentheses from phone input */
export function normalizePhone(value: string): string {
  return value.replace(/[\s\-().]/g, '')
}

/**
 * Normalize Zimbabwe mobiles for validation / storage.
 * Accepts local 0-prefix and international +263 forms, including a mistaken
 * leading zero after the country code (+263 071… → +263 71…).
 */
export function normalizeZimMobile(value: string): string {
  let n = normalizePhone(value)
  n = n.replace(/^(\+?263)0/, '$1')
  return n
}

/**
 * Zimbabwe mobile numbers: 07X XXX XXXX or +263 7X XXX XXXX
 * Valid operator prefixes: 71 (NetOne), 73 (Telecel), 77/78 (Econet)
 */
export function isValidZimMobile(value: string): boolean {
  const n = normalizeZimMobile(value)
  return /^(\+263|263|0)?7[1378]\d{7}$/.test(n)
}

export const formatZimPhoneHint = 'e.g. 071 123 4567 or +263 71 123 4567'

export const zimPhoneSchema = z
  .string()
  .trim()
  .min(1, 'Phone number is required')
  .refine(isValidZimMobile, {
    message: `Enter a valid Zimbabwe mobile number (${formatZimPhoneHint})`,
  })

export const zimPhoneOptionalSchema = z
  .string()
  .trim()
  .optional()
  .or(z.literal(''))
  .refine((val) => !val || isValidZimMobile(val), {
    message: `Enter a valid Zimbabwe mobile number (${formatZimPhoneHint})`,
  })

export function isValidIsoDate(value: string): boolean {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false
  const [y, m, d] = value.split('-').map(Number)
  const date = new Date(y, m - 1, d)
  return date.getFullYear() === y && date.getMonth() === m - 1 && date.getDate() === d
}

export function isPastDate(value: string): boolean {
  if (!isValidIsoDate(value)) return false
  const date = new Date(`${value}T00:00:00`)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return date <= today
}

export function ageInYears(dob: string): number {
  const birth = new Date(`${dob}T00:00:00`)
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age -= 1
  }
  return age
}

export function isStudentAge(dob: string, min = 3, max = 25): boolean {
  if (!isPastDate(dob)) return false
  const age = ageInYears(dob)
  return age >= min && age <= max
}

export const dateOfBirthSchema = z
  .string()
  .min(1, 'Date of birth is required')
  .refine(isValidIsoDate, 'Enter a valid date')
  .refine(isPastDate, 'Date of birth cannot be in the future')
  .refine((val) => isStudentAge(val), 'Student must be between 3 and 25 years old')

export const optionalDateSchema = z
  .string()
  .optional()
  .or(z.literal(''))
  .refine((val) => !val || isValidIsoDate(val), 'Enter a valid date')
  .refine((val) => !val || isPastDate(val), 'Date cannot be in the future')

export const futureDateSchema = z
  .string()
  .min(1, 'Due date is required')
  .refine(isValidIsoDate, 'Enter a valid date')
  .refine((val) => {
    const date = new Date(`${val}T00:00:00`)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    return date >= today
  }, 'Due date must be today or in the future')

export const emailOptionalSchema = z
  .string()
  .trim()
  .optional()
  .or(z.literal(''))
  .refine((val) => !val || z.string().email().safeParse(val).success, {
    message: 'Enter a valid email address',
  })

export const emailRequiredSchema = z
  .string()
  .trim()
  .min(1, 'Email is required')
  .email('Enter a valid email address')

function localCalendarIso(date: Date): string {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

export function studentDobBounds(): { min: string; max: string } {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const max = new Date(today.getFullYear() - 3, today.getMonth(), today.getDate())
  const min = new Date(today.getFullYear() - 25, today.getMonth(), today.getDate())
  return {
    min: localCalendarIso(min),
    max: localCalendarIso(max),
  }
}

export function todayIsoDate(): string {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return localCalendarIso(today)
}
