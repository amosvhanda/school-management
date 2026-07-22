import type { UserRole } from '@/types/auth'

export type StaffDashboardVariant =
  | 'admin'
  | 'teacher'
  | 'finance'
  | 'accounts'
  | 'examination_officer'

export function getStaffDashboardVariant(role: UserRole | null | undefined): StaffDashboardVariant {
  switch (role) {
    case 'teacher':
      return 'teacher'
    case 'finance':
      return 'finance'
    case 'accounts':
      return 'accounts'
    case 'examination_officer':
      return 'examination_officer'
    default:
      return 'admin'
  }
}

export interface RoleDashboardMeta {
  variant: StaffDashboardVariant
  label: string
  subtitle: string
  pageTitle: string
}

const META: Record<StaffDashboardVariant, Omit<RoleDashboardMeta, 'variant'>> = {
  admin: {
    label: 'Administrator',
    subtitle: 'Executive overview of enrolment, finance, attendance, and school operations.',
    pageTitle: 'Admin dashboard',
  },
  teacher: {
    label: 'Teacher',
    subtitle: 'Your classes, today’s lessons, mark register, and teaching workflows.',
    pageTitle: 'Teacher dashboard',
  },
  finance: {
    label: 'Finance',
    subtitle: 'Fee collections, outstanding balances, and revenue performance.',
    pageTitle: 'Finance dashboard',
  },
  accounts: {
    label: 'Accounts',
    subtitle: 'Payments, payroll, transactions, and daily accounting tasks.',
    pageTitle: 'Accounts dashboard',
  },
  examination_officer: {
    label: 'Examination officer',
    subtitle: 'Exam schedules, mark entry, approvals, and result publication.',
    pageTitle: 'Exams dashboard',
  },
}

export function getRoleDashboardMeta(role: UserRole | null | undefined): RoleDashboardMeta {
  const variant = getStaffDashboardVariant(role)
  return { variant, ...META[variant] }
}
