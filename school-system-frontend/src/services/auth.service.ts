import { api } from '@/lib/api'
import { unwrapOne } from '@/lib/api-response'
import type { ApiResponse, LicenseStatus } from '@/types/api'
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

export async function switchSchool(schoolId: number) {
  const { data } = await api.post<ApiResponse<LoginResponse>>(endpoints.auth.switchSchool, {
    school_id: schoolId,
  })
  return unwrapOne<LoginResponse>(data)
}

export interface PlatformTermsPayload {
  version: string
  title: string
  summary: string
  content: string
}

export async function fetchPlatformTerms() {
  const { data } = await api.get<ApiResponse<PlatformTermsPayload>>(endpoints.auth.platformTerms)
  return unwrapOne<PlatformTermsPayload>(data)
}

export async function fetchPrivacyPolicy() {
  const { data } = await api.get<ApiResponse<PlatformTermsPayload>>(endpoints.auth.privacyPolicy)
  return unwrapOne<PlatformTermsPayload>(data)
}

export async function acceptPlatformTerms(version: string) {
  const { data } = await api.post<ApiResponse<{ user: AuthUser }>>(endpoints.auth.acceptPlatformTerms, {
    accepted: true,
    version,
  })
  return unwrapOne<{ user: AuthUser }>(data).user
}

export async function activateLicense(licenseKey: string) {
  const { data } = await api.post(endpoints.license.activate, { license_key: licenseKey })
  return unwrapOne<Record<string, unknown>>(data)
}

export async function fetchLicenseStatus() {
  const { data } = await api.get(endpoints.license.status)
  return unwrapOne<LicenseStatus>(data)
}

export async function forgotPassword(payload: { email: string }) {
  const { data } = await api.post(endpoints.auth.forgotPassword, payload)
  return data
}

export async function resetPassword(payload: {
  email: string
  password: string
  password_confirmation: string
  token: string
}) {
  const { data } = await api.post(endpoints.auth.resetPassword, payload)
  return data
}
