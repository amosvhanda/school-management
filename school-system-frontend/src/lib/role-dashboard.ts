import type { AuthUser, UserRole } from '@/types/auth'
import type { DashboardModuleGroup } from '@/lib/dashboard-modules'
import {
  ACCOUNTS_DASHBOARD_MODULE_GROUPS,
  EXAM_OFFICER_DASHBOARD_MODULE_GROUPS,
  FINANCE_DASHBOARD_MODULE_GROUPS,
  STAFF_DASHBOARD_MODULE_GROUPS,
  TEACHER_DASHBOARD_MODULE_GROUPS,
} from '@/lib/dashboard-modules'

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
    subtitle: 'Today’s classes, register, and marks — use Teaching for plans and homework.',
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

/** Module shortcuts for each staff home — filtered further by capability in the grid. */
export function getDashboardModuleGroupsForVariant(
  variant: StaffDashboardVariant,
  _user?: AuthUser | null,
): DashboardModuleGroup[] {
  switch (variant) {
    case 'teacher':
      return TEACHER_DASHBOARD_MODULE_GROUPS
    case 'finance':
      return FINANCE_DASHBOARD_MODULE_GROUPS
    case 'accounts':
      return ACCOUNTS_DASHBOARD_MODULE_GROUPS
    case 'examination_officer':
      return EXAM_OFFICER_DASHBOARD_MODULE_GROUPS
    default:
      return STAFF_DASHBOARD_MODULE_GROUPS
  }
}
