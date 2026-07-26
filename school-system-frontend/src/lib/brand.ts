import { appName } from '@/lib/env'

/** Product name shown in chrome and auth (from VITE_APP_NAME). */
export const brandName = appName

/** Short line under the logo on auth screens. */
export const brandTagline = 'School operations, built for Zimbabwe'

/** Auth brand-panel defaults. */
export const brandPanel = {
  eyebrow: 'Built for Zimbabwe schools',
  title: 'Academics, fees, and families in one place',
  body: 'Attendance, invoices, gradebook, and parent access — designed for day-to-day school operations.',
} as const
