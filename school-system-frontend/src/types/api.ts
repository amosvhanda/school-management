export interface ApiResponse<T = unknown> {
  message: string
  data?: T
  meta?: Record<string, unknown>
  errors?: Record<string, string[]>
}

export interface ApiErrorResponse {
  message: string
  errors?: Record<string, string[]>
  code?: string
  license?: LicenseStatus
}

export interface LicenseStatus {
  status: string
  message?: string
  expires_at?: string | null
  grace_days_remaining?: number
}

export interface Paginator<T> {
  current_page: number
  data: T[]
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface ListQueryParams {
  all?: boolean
  limit?: number
  sort?: string
  order?: 'asc' | 'desc'
  search?: string
  page?: number
  per_page?: number
  [key: string]: string | number | boolean | undefined
}
