import { api } from '@/lib/api'
import { unwrapOne } from '@/lib/api-response'
import type { ApiResponse } from '@/types/api'
import type { AuthUser, LoginResponse } from '@/types/auth'
import { endpoints } from './endpoints'

export async function login(email: string, password: string, role?: string) {
  const { data } = await api.post<ApiResponse<LoginResponse>>(endpoints.auth.login, {
    email,
    password,
    ...(role ? { role } : {}),
  })
  return unwrapOne<LoginResponse>(data)
}

export async function logout() {
  await api.post(endpoints.auth.logout)
}

export async function fetchCurrentUser() {
  const { data } = await api.get<ApiResponse<{ user: AuthUser }>>(endpoints.auth.me)
  return unwrapOne<{ user: AuthUser }>(data).user
}

export async function activateLicense(licenseKey: string) {
  const { data } = await api.post(endpoints.license.activate, { license_key: licenseKey })
  return unwrapOne<Record<string, unknown>>(data)
}

export async function fetchLicenseStatus() {
  const { data } = await api.get(endpoints.license.status)
  return unwrapOne<Record<string, unknown>>(data)
}
