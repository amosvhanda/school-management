import axios from 'axios'
import type { Router } from 'vue-router'
import { env } from '@/lib/env'
import { getErrorMessage } from '@/lib/api-response'
import { finishRequestProgress, startRequestProgress } from '@/lib/request-progress'
import type { ApiErrorResponse } from '@/types/api'

const apiOrigin = env.VITE_API_URL?.replace(/\/$/, '') ?? ''
export const baseURL = apiOrigin ? `${apiOrigin}/api/v1` : '/api/v1'

/**
 * Encode nested query objects the way Spatie Query Builder expects:
 *   { filter: { status: 'active' }, fields: { students: 'id,name' } }
 *   -> filter[status]=active&fields[students]=id,name
 */
export function serializeParams(params: Record<string, unknown>): string {
  const parts: string[] = []

  const append = (key: string, value: unknown) => {
    if (value === undefined || value === null || value === '') return
    if (typeof value === 'boolean') {
      parts.push(`${encodeURIComponent(key)}=${value ? '1' : '0'}`)
      return
    }
    if (Array.isArray(value)) {
      parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(value.join(','))}`)
      return
    }
    parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
  }

  for (const [key, value] of Object.entries(params)) {
    if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
      for (const [nestedKey, nestedValue] of Object.entries(value as Record<string, unknown>)) {
        if (nestedValue === undefined || nestedValue === null || nestedValue === '') continue
        append(`${key}[${nestedKey}]`, nestedValue)
      }
      continue
    }
    append(key, value)
  }

  return parts.join('&')
}

export const api = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  paramsSerializer: {
    serialize: serializeParams,
  },
})

const TOKEN_KEY = 'auth_token'

export function getStoredToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setStoredToken(token: string | null): void {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

let routerInstance: Router | null = null
let onUnauthorized: (() => void) | null = null
let onForbidden: ((message: string) => void) | null = null
let onLicenseRequired: ((payload: ApiErrorResponse) => void) | null = null

export function setupApiInterceptors(options: {
  router: Router
  onUnauthorized?: () => void
  onForbidden?: (message: string) => void
  onLicenseRequired?: (payload: ApiErrorResponse) => void
}) {
  routerInstance = options.router
  onUnauthorized = options.onUnauthorized ?? null
  onForbidden = options.onForbidden ?? null
  onLicenseRequired = options.onLicenseRequired ?? null
}

api.interceptors.request.use((config) => {
  if (!config.headers['X-Skip-Progress']) {
    startRequestProgress()
  }

  const token = getStoredToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => {
    if (!response.config.headers['X-Skip-Progress']) {
      finishRequestProgress()
    }
    return response
  },
  (error) => {
    if (!error.config?.headers?.['X-Skip-Progress']) {
      finishRequestProgress()
    }
    const status = error.response?.status
    const data = error.response?.data as ApiErrorResponse | undefined

    if (status === 401) {
      setStoredToken(null)
      onUnauthorized?.()
      if (routerInstance && routerInstance.currentRoute.value.name !== 'login') {
        routerInstance.push({ name: 'login', query: { redirect: routerInstance.currentRoute.value.fullPath } })
      }
    }

    if (status === 402 && data?.code?.startsWith('license')) {
      onLicenseRequired?.(data)
      if (routerInstance && !routerInstance.currentRoute.value.path.startsWith('/license')) {
        routerInstance.push({ name: 'license-activate' })
      }
    }

    if (status === 403 && data?.code === 'two_factor_setup_required') {
      if (routerInstance && routerInstance.currentRoute.value.name !== 'my-profile') {
        routerInstance.push({ name: 'my-profile', query: { setup: '2fa' } })
      }
    }

    if (status === 403) {
      const message = getErrorMessage(error, 'You do not have permission to perform this action.')
      // Page-level handlers already surface this soft teacher-portal failure.
      if (!message.includes('Teacher profile not linked to this account.') && data?.code !== 'two_factor_setup_required') {
        onForbidden?.(message)
      }
    }

    return Promise.reject(error)
  },
)
