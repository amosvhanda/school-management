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
  guardians: { create: STUDENTS, edit: STUDENTS, delete: ADMIN },
  enrollment: { create: STUDENTS, edit: STUDENTS, delete: ADMIN },
  'admin-users': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'admin-roles': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  settings: { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'settings-custom-fields': { create: ADMIN, edit: ADMIN, delete: ADMIN },
  'compliance-audit': { create: AUDIT, edit: AUDIT, delete: AUDIT },
  'compliance-login-history': { create: AUDIT, edit: AUDIT, delete: AUDIT },
}

function inferPermissions(listKey: string): ModulePermissionConfig {
  if (listKey.startsWith('finance-') || listKey.startsWith('enterprise-finance')) {
    return { create: FINANCE, edit: FINANCE, delete: FINANCE }
  }
  if (
    listKey.startsWith('academics-')
    && (listKey.includes('exam') || listKey.includes('test') || listKey.includes('grade'))
  ) {
    return { create: EXAMS, edit: EXAMS, delete: ADMIN }
  }
  if (listKey.startsWith('academics-')) {
    return { create: ADMIN, edit: ADMIN, delete: ADMIN }
  }
  if (listKey.startsWith('enterprise-exams')) {
    return { create: EXAMS, edit: EXAMS, delete: ADMIN }
  }
  if (listKey.startsWith('hr-discipline') || listKey.startsWith('compliance-consent')) {
    return { create: STUDENTS, edit: STUDENTS, delete: ADMIN }
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
