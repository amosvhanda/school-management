import type { AuthUser } from '@/types/auth'
import type { NavCapability } from '@/types/navigation'
import { hasCapability } from '@/lib/permissions'
import type { ModuleCrudConfig } from './registry-crud'

type Cap = NavCapability

interface ModulePermissionConfig {
  create?: Cap
  edit?: Cap
  delete?: Cap
}

const ADMIN: Cap = 'canManageTeachers'
const STUDENTS: Cap = 'canManageStudents'
const FINANCE: Cap = 'canManageFinance'
const EXAMS: Cap = 'canManageExaminations'
const AUDIT: Cap = 'canViewAuditLogs'
const STAFF: Cap = 'isStaff'

/** Explicit CRUD capability overrides per registry list key. */
const modulePermissionRegistry: Record<string, ModulePermissionConfig> = {
  students: { create: STUDENTS, edit: STUDENTS, delete: ADMIN },
  teachers: { create: ADMIN, edit: ADMIN, delete: ADMIN },
  guardians: { create: ADMIN, edit: ADMIN, delete: ADMIN },
  enrollment: { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'admin-users': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'admin-roles': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  settings: { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'settings-custom-fields': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'compliance-audit': { create: AUDIT, edit: AUDIT, delete: AUDIT },
  'compliance-login-history': { create: AUDIT, edit: AUDIT, delete: AUDIT },
  'finance-income': { create: FINANCE, edit: FINANCE, delete: FINANCE },
  'finance-expense': { create: FINANCE, edit: FINANCE, delete: FINANCE },
  'finance-budgets': { create: FINANCE, edit: FINANCE, delete: FINANCE },
  'ops-assets': { create: FINANCE, edit: FINANCE, delete: FINANCE },
  'ops-procurement': { create: FINANCE, edit: FINANCE, delete: FINANCE },
  'ops-procurement-vendors': { create: FINANCE, edit: FINANCE, delete: FINANCE },
  'ops-hostels': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'ops-health': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'ops-events': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'enterprise-academic': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'enterprise-alumni': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'ops-school-trips': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'people-student-categories': { create: STUDENTS, edit: STUDENTS, delete: ADMIN },
  'hr-employees': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'hr-designations': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'hr-leave-types': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'settings-currencies': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'settings-languages': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  compliance: { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'compliance-incidents': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'compliance-consent': { create: ADMIN, edit: ADMIN, delete: ADMIN },
}

function inferPermissions(listKey: string): ModulePermissionConfig {
  if (listKey.startsWith('finance-') || listKey.startsWith('enterprise-finance')) {
    return { create: FINANCE, edit: FINANCE, delete: FINANCE }
  }
  if (
    listKey.startsWith('academics-')
    && (
      listKey.includes('exam')
      || listKey.includes('test')
      // Gradebook only — not grade-levels / grading-scales setup
      || listKey === 'academics-grades'
    )
  ) {
    return { create: EXAMS, edit: EXAMS, delete: ADMIN }
  }
  if (listKey.startsWith('academics-')) {
    return { create: ADMIN, edit: ADMIN, delete: ADMIN }
  }
  if (listKey.startsWith('ops-library')) {
    return { create: 'canManageLibrary', edit: 'canManageLibrary', delete: 'canManageLibrary' }
  }
  if (listKey.startsWith('ops-transport')) {
    return { create: 'canManageTransport', edit: 'canManageTransport', delete: 'canManageTransport' }
  }
  if (listKey.startsWith('ops-inventory')) {
    return { create: 'canManageInventory', edit: 'canManageInventory', delete: 'canManageInventory' }
  }
  if (listKey.startsWith('ops-visitors')) {
    return { create: 'canManageReception', edit: 'canManageReception', delete: 'canManageReception' }
  }
  if (listKey.startsWith('ops-help-desk')) {
    return { create: 'canManageReception', edit: 'canManageReception', delete: 'canManageReception' }
  }
  if (listKey.startsWith('enterprise-exams')) {
    return { create: EXAMS, edit: EXAMS, delete: ADMIN }
  }
  if (listKey.startsWith('hr-discipline')) {
    return { create: STUDENTS, edit: STUDENTS, delete: ADMIN }
  }
  if (listKey.startsWith('compliance-consent')) {
    return { create: ADMIN, edit: ADMIN, delete: ADMIN }
  }
  if (listKey.startsWith('platform-')) {
    return { create: 'isSuperAdmin', edit: 'isSuperAdmin', delete: 'isSuperAdmin' }
  }
  if (listKey.startsWith('portal-')) {
    return { create: 'isParent', edit: 'isParent', delete: 'isParent' }
  }
  return { create: STAFF, edit: STAFF, delete: ADMIN }
}

function resolveCap(user: AuthUser, cap: Cap): boolean {
  return hasCapability(user, cap)
}

export function resolveCrudAccess(
  user: AuthUser | null,
  listKey: string,
  config: Pick<ModuleCrudConfig, 'canCreate' | 'canEdit' | 'canDelete'>,
): { canCreate: boolean; canEdit: boolean; canDelete: boolean } {
  if (!user) {
    return { canCreate: false, canEdit: false, canDelete: false }
  }

  const perms = modulePermissionRegistry[listKey] ?? inferPermissions(listKey)

  return {
    canCreate: config.canCreate !== false && resolveCap(user, perms.create ?? STAFF),
    canEdit: config.canEdit !== false && resolveCap(user, perms.edit ?? STAFF),
    canDelete: config.canDelete !== false && resolveCap(user, perms.delete ?? ADMIN),
  }
}
