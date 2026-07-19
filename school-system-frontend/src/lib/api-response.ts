import type { ApiResponse, Paginator } from '@/types/api'

export function unwrapOne<T>(body: unknown): T {
  if (body && typeof body === 'object') {
    if ('data' in body && (body as ApiResponse<T>).data !== undefined) {
      return (body as ApiResponse<T>).data as T
    }
  }
  return body as T
}

export function unwrapList<T>(body: unknown): T[] {
  if (Array.isArray(body)) return body as T[]

  if (body && typeof body === 'object') {
    if ('data' in body) {
      const data = (body as { data: unknown }).data
      if (Array.isArray(data)) return data as T[]
      if (data && typeof data === 'object' && 'data' in (data as Paginator<T>)) {
        return (data as Paginator<T>).data
      }
    }
  }

  return []
}

export function isPaginator<T>(body: unknown): body is Paginator<T> {
  return (
    body !== null &&
    typeof body === 'object' &&
    'data' in body &&
    'current_page' in body &&
    'last_page' in body
  )
}

export function unwrapPaginator<T>(body: unknown): Paginator<T> {
  if (isPaginator<T>(body)) return body

  if (body && typeof body === 'object') {
    // Laravel Resource collection: { data: [...], meta: { current_page, ... }, links?: ... }
    if ('data' in body && 'meta' in body) {
      const meta = (body as { meta: Record<string, unknown> }).meta
      const data = unwrapList<T>(body)
      if (typeof meta.current_page === 'number' || typeof meta.total === 'number') {
        return {
          current_page: Number(meta.current_page ?? 1),
          data,
          last_page: Number(meta.last_page ?? 1),
          per_page: Number(meta.per_page ?? data.length),
          total: Number(meta.total ?? data.length),
          from: (meta.from as number | null | undefined) ?? (data.length ? 1 : null),
          to: (meta.to as number | null | undefined) ?? (data.length || null),
        }
      }
    }

    if ('data' in body) {
      const inner = (body as { data: unknown }).data
      if (isPaginator<T>(inner)) return inner
    }
  }

  const list = unwrapList<T>(body)
  return {
    current_page: 1,
    data: list,
    last_page: 1,
    per_page: list.length,
    total: list.length,
    from: list.length ? 1 : null,
    to: list.length || null,
  }
}

export function getErrorMessage(error: unknown, fallback = 'Something went wrong'): string {
  if (typeof error === 'object' && error !== null && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string }; status?: number } }).response
    if (response?.data?.message) return response.data.message
    if (response?.status === 401) return 'Session expired. Please sign in again.'
    if (response?.status === 402) return 'School license required. Activate your license to continue.'
    if (response?.status === 403) return 'You do not have permission to access this resource.'
    if (response?.status === 404) return 'API endpoint not found. Check that the backend is up to date.'
    if (response?.status && response.status >= 500) return 'Server error. Check Laravel logs and try again.'
  }
  if (typeof error === 'object' && error !== null && 'message' in error) {
    const message = String((error as { message?: string }).message ?? '')
    if (message === 'Network Error') {
      return 'Cannot reach the API. Start Laravel (php artisan serve) and verify VITE_API_URL in school-system-frontend/.env.'
    }
    if (message) return message
  }
  return fallback
}

export function getValidationErrors(error: unknown): Record<string, string[]> {
  if (typeof error === 'object' && error !== null && 'response' in error) {
    const response = (error as { response?: { data?: { errors?: Record<string, string[]> } } }).response
    return response?.data?.errors ?? {}
  }
  return {}
}
