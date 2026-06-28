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
  if (body && typeof body === 'object' && 'data' in body) {
    const inner = (body as { data: unknown }).data
    if (isPaginator<T>(inner)) return inner
  }
  return {
    current_page: 1,
    data: unwrapList<T>(body),
    last_page: 1,
    per_page: unwrapList<T>(body).length,
    total: unwrapList<T>(body).length,
    from: unwrapList<T>(body).length ? 1 : null,
    to: unwrapList<T>(body).length,
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
