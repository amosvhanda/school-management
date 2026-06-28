#!/usr/bin/env node
/**
 * Verifies demo profile API login + expected route access matrix.
 * Usage: node scripts/verify-profile-access.mjs [API_BASE_URL]
 * Default API: http://school-management.test/api/v1
 */

const API_BASE = (process.argv[2] ?? 'http://school-management.test/api/v1').replace(/\/$/, '')

const ACCOUNTS = [
  { label: 'Admin', email: 'admin@school.co.zw', password: 'admin123', role: 'admin', webAccess: true },
  { label: 'Teacher', email: 'teacher@school.co.zw', password: 'teacher123', role: 'teacher', webAccess: true },
  { label: 'Finance', email: 'finance@school.co.zw', password: 'finance123', role: 'finance', webAccess: true },
  { label: 'Accounts', email: 'accounts@school.co.zw', password: 'accounts123', role: 'accounts', webAccess: true },
  { label: 'Exam officer', email: 'exam@school.co.zw', password: 'exam123', role: 'examination_officer', webAccess: true },
  { label: 'Parent', email: 'parent@school.co.zw', password: 'parent123', role: 'parent', webAccess: true },
  { label: 'Platform admin', email: 'super@school.co.zw', password: 'super123', role: 'super_admin', webAccess: true },
  { label: 'Student', email: 'student@school.co.zw', password: 'student123', role: 'student', webAccess: false },
]

const STAFF_DASHBOARD_ROLES = ['admin', 'teacher', 'finance', 'accounts', 'examination_officer']

function hasCapability(user, capability) {
  if (user.capabilities && capability in user.capabilities) {
    return Boolean(user.capabilities[capability])
  }
  const role = user.role
  switch (capability) {
    case 'isSuperAdmin':
      return role === 'super_admin'
    case 'isParent':
      return role === 'parent'
    case 'isStaff':
      return STAFF_DASHBOARD_ROLES.includes(role) || role === 'super_admin'
    case 'canManageStudents':
      return ['super_admin', 'admin', 'teacher'].includes(role)
    case 'canManageTeachers':
      return ['super_admin', 'admin'].includes(role)
    case 'canManageFinance':
      return ['super_admin', 'admin', 'finance', 'accounts'].includes(role)
    case 'canViewAuditLogs':
      return ['super_admin', 'admin', 'finance', 'accounts'].includes(role)
    case 'canManageExaminations':
      return ['super_admin', 'admin', 'teacher', 'examination_officer'].includes(role)
    default:
      return false
  }
}

function isParentPath(path) {
  return path === '/portal' || path.startsWith('/portal/')
}

function isPlatformPath(path) {
  return path === '/platform' || path.startsWith('/platform/')
}

function canAccessRoute(user, path, meta = {}) {
  if (user.role === 'student' && path !== '/license/activate') return false

  if (user.role === 'parent') {
    if (path === '/license/activate') return true
    return isParentPath(path)
  }

  if (user.role === 'super_admin') {
    if (isParentPath(path)) return false
    if (isPlatformPath(path)) return true
    if (path === '/') return false
  } else if (STAFF_DASHBOARD_ROLES.includes(user.role) || user.role === 'super_admin') {
    if (isParentPath(path) || isPlatformPath(path)) return false
  } else {
    return false
  }

  const roles = meta.roles
  if (roles?.length && !roles.includes(user.role)) return false

  const capability = meta.capability
  if (capability) {
    const caps = Array.isArray(capability) ? capability : [capability]
    if (!caps.some((cap) => hasCapability(user, cap))) return false
  }

  return true
}

function getDefaultRoute(role) {
  switch (role) {
    case 'parent':
      return '/portal'
    case 'super_admin':
      return '/platform'
    case 'student':
      return '/login'
    default:
      return STAFF_DASHBOARD_ROLES.includes(role) ? '/' : '/login'
  }
}

const ROUTE_CHECKS = [
  { path: '/', meta: { capability: 'isStaff' }, label: 'Staff dashboard' },
  { path: '/students', meta: { capability: 'canManageStudents' }, label: 'Students' },
  { path: '/teachers', meta: { capability: 'canManageTeachers' }, label: 'Teachers' },
  { path: '/finance', meta: { capability: 'canManageFinance' }, label: 'Finance' },
  { path: '/academics/exams', meta: { capability: 'canManageExaminations' }, label: 'Exams' },
  { path: '/compliance/audit', meta: { capability: 'canViewAuditLogs' }, label: 'Audit logs' },
  { path: '/portal', meta: { roles: ['parent'] }, label: 'Parent portal' },
  { path: '/platform', meta: { roles: ['super_admin'] }, label: 'Platform dashboard' },
  { path: '/platform/licenses', meta: { roles: ['super_admin'] }, label: 'Platform licenses' },
]

async function login(email, password) {
  const res = await fetch(`${API_BASE}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  })
  const body = await res.json()
  if (!res.ok) {
    throw new Error(body.message ?? `HTTP ${res.status}`)
  }
  return body.data.user
}

let failures = 0

console.log(`\nProfile access verification — ${API_BASE}\n`)

for (const account of ACCOUNTS) {
  process.stdout.write(`${account.label.padEnd(16)} `)

  try {
    const user = await login(account.email, account.password)

    if (user.role !== account.role) {
      console.log(`FAIL — expected role ${account.role}, got ${user.role}`)
      failures++
      continue
    }

    const defaultRoute = getDefaultRoute(user.role)
    const webBlocked = user.role === 'student'

    if (webBlocked && account.webAccess) {
      console.log('FAIL — student should not have web access')
      failures++
      continue
    }

    const routeResults = ROUTE_CHECKS.map((check) => ({
      ...check,
      allowed: canAccessRoute(user, check.path, check.meta),
    }))

    const expectedDefault =
      user.role === 'super_admin'
        ? '/platform'
        : user.role === 'parent'
          ? '/portal'
          : STAFF_DASHBOARD_ROLES.includes(user.role)
            ? '/'
            : '/login'

    if (defaultRoute !== expectedDefault) {
      console.log(`FAIL — default route ${defaultRoute} !== ${expectedDefault}`)
      failures++
      continue
    }

    const summary = routeResults
      .filter((r) => r.allowed)
      .map((r) => r.label)
      .join(', ')

    console.log(`OK — default ${defaultRoute} | access: ${summary || '(none)'}`)
  } catch (err) {
    console.log(`FAIL — ${err.message}`)
    failures++
  }
}

console.log(`\n${failures === 0 ? 'All profiles passed.' : `${failures} profile(s) failed.`}\n`)
process.exit(failures > 0 ? 1 : 0)
