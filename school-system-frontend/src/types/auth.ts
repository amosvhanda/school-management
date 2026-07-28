import type { LicenseStatus } from './api'
import type { NavCapability } from './navigation'

export type UserRole =
  | 'super_admin'
  | 'admin'
  | 'teacher'
  | 'parent'
  | 'student'
  | 'finance'
  | 'accounts'
  | 'examination_officer'
  | 'receptionist'
  | 'librarian'
  | 'nurse'
  | 'transport_manager'
  | 'hostel_manager'

export type UserCapabilities = Record<NavCapability, boolean>

export interface ParentChild {
  id: number
  fullName: string
  studentNumber: string
  class: string
}

export interface AuthSchool {
  id: number
  name: string
  code?: string | null
  status?: string
  is_current?: boolean
  is_default?: boolean
}

export interface AuthUser {
  id: number
  name: string
  first_name?: string
  last_name?: string
  email: string
  phone?: string | null
  role: UserRole
  avatar_url?: string | null
  status?: string
  school_id?: number | null
  schools?: AuthSchool[]
  student_id?: number | null
  teacher_id?: number | null
  class_id?: number | null
  class_name?: string | null
  guardian_id?: number | null
  children?: ParentChild[]
  license?: LicenseStatus
  permissions?: string[]
  capabilities?: UserCapabilities
  platform_terms_accepted?: boolean
  platform_terms_version?: string | null
  platform_terms_accepted_at?: string | null
  platform_terms_required_version?: string
  created_at?: string
  updated_at?: string
}

export interface LoginResponse {
  user: AuthUser
  token: string
}
