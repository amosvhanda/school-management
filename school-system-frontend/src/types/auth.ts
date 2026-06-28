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

export type UserCapabilities = Record<NavCapability, boolean>

export interface ParentChild {
  id: number
  fullName: string
  studentNumber: string
  class: string
}

export interface AuthUser {
  id: number
  name: string
  first_name?: string
  last_name?: string
  email: string
  phone?: string | null
  role: UserRole
  status?: string
  school_id?: number | null
  student_id?: number | null
  teacher_id?: number | null
  guardian_id?: number | null
  children?: ParentChild[]
  license?: LicenseStatus
  permissions?: string[]
  capabilities?: UserCapabilities
  created_at?: string
  updated_at?: string
}

export interface LoginResponse {
  user: AuthUser
  token: string
}
