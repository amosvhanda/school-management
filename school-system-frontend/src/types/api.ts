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
  plan?: string | null
  expires_at?: string | null
  days_remaining?: number | null
  grace_ends_at?: string | null
  grace_days_remaining?: number
  enforcement?: boolean
  active_key?: {
    id: number
    plan_type: string
    activated_at?: string | null
    expires_at?: string | null
  } | null
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
  /** Spatie-style exact/partial filters: encoded as filter[status]=active */
  filter?: Record<string, string | number | boolean | undefined | null>
  /** Comma-separated relation includes */
  include?: string
  /** Sparse fieldsets: fields[students]=id,full_name */
  fields?: Record<string, string>
  [key: string]:
    | string
    | number
    | boolean
    | undefined
    | null
    | Record<string, string | number | boolean | undefined | null>
    | Record<string, string>
}
