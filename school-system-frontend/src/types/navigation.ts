import type { UserRole } from './auth'

export type NavCapability =
  | 'canManageStudents'
  | 'canManageTeachers'
  | 'canManageFinance'
  | 'canViewAuditLogs'
  | 'canManageExaminations'
  | 'canEnterExamResults'
  | 'canManageLibrary'
  | 'canManageTransport'
  | 'canManageInventory'
  | 'canManageReception'
  | 'isSuperAdmin'
  | 'isParent'
  | 'isStaff'

export interface NavItem {
  title: string
  href?: string
  icon?: string
  capability?: NavCapability | NavCapability[]
  roles?: UserRole[]
  badge?: string
  items?: NavItem[]
}

export interface NavGroup {
  label: string
  items: NavItem[]
}
