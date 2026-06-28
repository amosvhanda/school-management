export const AUDIT_MODULES: Record<string, string> = {
  auth: 'Authentication',
  student: 'Students',
  administration: 'Administration',
  finance: 'Finance',
  examination: 'Examinations',
  discipline: 'Discipline',
  enrollment: 'Enrollment',
  attendance: 'Attendance',
  guardian: 'Guardians',
  hr: 'Human resources',
  workflow: 'Workflows',
  reports: 'Reports',
  system: 'System',
}

export const AUDIT_ACTIONS: Record<string, string> = {
  created: 'Created',
  updated: 'Updated',
  deleted: 'Deleted',
  login: 'Signed in',
  logout: 'Signed out',
  failed_login: 'Failed sign-in',
  export: 'Exported',
  payment_reversed: 'Reversed payment',
  approved: 'Approved',
  published: 'Published',
  rejected: 'Rejected',
  recorded: 'Recorded',
}

export function auditModuleLabel(module?: string | null): string {
  if (!module) return 'System'
  return AUDIT_MODULES[module] ?? module.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

export function auditActionLabel(action?: string | null): string {
  if (!action) return 'Action'
  return AUDIT_ACTIONS[action] ?? action.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

export function roleLabel(role?: string | null): string {
  if (!role) return 'Unknown role'
  return role.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

export type AuditActionTone = 'default' | 'secondary' | 'destructive' | 'outline'

export function auditActionTone(action?: string | null): AuditActionTone {
  switch (action) {
    case 'deleted':
    case 'payment_reversed':
    case 'failed_login':
      return 'destructive'
    case 'created':
    case 'login':
    case 'approved':
    case 'published':
      return 'default'
    case 'rejected':
      return 'destructive'
    case 'updated':
    case 'export':
    case 'recorded':
      return 'secondary'
    default:
      return 'outline'
  }
}
