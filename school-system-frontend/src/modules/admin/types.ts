export interface PermissionRecord {
  id: number
  name: string
  slug: string
  resource: string
  action: string
  description?: string
  capabilities?: string[]
}

export interface RoleRecord {
  id: number
  name: string
  slug: string
  description?: string | null
  permissions?: PermissionRecord[]
  permission_ids?: number[]
  user_count?: number
  is_system?: boolean
  created_at?: string
  updated_at?: string
}

export const SYSTEM_ROLE_SLUGS = [
  'admin',
  'teacher',
  'parent',
  'student',
  'finance',
  'accounts',
  'examination_officer',
  'receptionist',
  'librarian',
  'nurse',
  'transport_manager',
  'hostel_manager',
] as const

export function resourceLabel(resource: string): string {
  return resource.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

export function groupPermissions(permissions: PermissionRecord[]): Record<string, PermissionRecord[]> {
  return permissions.reduce<Record<string, PermissionRecord[]>>((groups, permission) => {
    const key = permission.resource || 'general'
    if (!groups[key]) groups[key] = []
    groups[key].push(permission)
    return groups
  }, {})
}
