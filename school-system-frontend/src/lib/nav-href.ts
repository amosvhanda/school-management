/**
 * Canonical destinations for sidebar links.
 * Prefer final hub URLs (path + optional tab/status) so active states match after redirects.
 */

export interface NavTarget {
  path: string
  tab: string | null
  /** List filter intent (e.g. suspended students). */
  status?: string | null
  /** Staff attendance scope (teacher | employee). */
  staff?: string | null
}

/** Pretty redirect paths → where the router actually lands. */
const REDIRECT_TARGETS: Record<string, NavTarget> = {
  '/students': { path: '/people', tab: null, status: null },
  '/students/suspended': { path: '/people', tab: null, status: 'suspended' },
  '/teachers': { path: '/people', tab: 'teachers' },
  '/guardians': { path: '/people', tab: 'guardians' },
  '/enrollment': { path: '/people', tab: 'enrollment' },
  '/people/categories': { path: '/people', tab: 'student-categories' },
  '/finance/payments': { path: '/finance', tab: 'payments' },
  '/finance/invoices': { path: '/finance', tab: 'invoices' },
  '/finance/fees': { path: '/finance', tab: 'fee-structures' },
  '/finance/fee-categories': { path: '/finance', tab: 'fee-categories' },
  '/finance/fee-groups': { path: '/finance', tab: 'fee-groups' },
  '/finance/fee-discounts': { path: '/finance', tab: 'fee-discounts' },
  '/finance/fee-structures': { path: '/finance', tab: 'fee-structures' },
  '/finance/transactions': { path: '/finance', tab: 'transactions' },
  '/finance/payroll': { path: '/finance', tab: 'payroll' },
  '/finance/cash-flow': { path: '/finance', tab: 'cash-flow' },
  '/finance/reports': { path: '/finance', tab: 'aging' },
  '/finance/accounting': { path: '/finance', tab: 'income-heads' },
  '/finance/income-heads': { path: '/finance', tab: 'income-heads' },
  '/finance/expense-heads': { path: '/finance', tab: 'expense-heads' },
  '/finance/income': { path: '/finance', tab: 'income' },
  '/finance/expense': { path: '/finance', tab: 'expense' },
  '/finance/procurement': { path: '/finance', tab: 'procurement' },
  '/finance/assets': { path: '/finance', tab: 'assets' },
  '/operations/inventory': { path: '/operations', tab: null },
  '/operations/inventory/sales': { path: '/operations', tab: 'inventory' },
  '/operations/library': { path: '/operations', tab: 'library-books' },
  '/operations/library/books': { path: '/operations', tab: 'library-books' },
  '/operations/library/members': { path: '/operations', tab: 'library-members' },
  '/operations/library/loans': { path: '/operations', tab: 'library-loans' },
  '/operations/transport': { path: '/operations', tab: 'transport' },
  '/operations/transport/drivers': { path: '/operations', tab: 'transport' },
  '/operations/transport/routes': { path: '/operations', tab: 'transport' },
  '/operations/hostels': { path: '/operations', tab: 'hostels' },
  '/operations/front-office': { path: '/operations', tab: 'front-office' },
  '/operations/visitors': { path: '/operations', tab: 'front-office' },
  '/operations/help-desk': { path: '/operations', tab: 'front-office' },
  '/operations/health': { path: '/operations', tab: 'health' },
  '/operations/events': { path: '/operations', tab: 'events' },
  '/operations/school-trips': { path: '/operations', tab: 'trips' },
  '/operations/assets': { path: '/finance', tab: 'assets' },
  '/operations/procurement': { path: '/finance', tab: 'procurement' },
  '/hr/leave': { path: '/hr', tab: 'leave' },
  '/hr/leave-types': { path: '/hr', tab: 'leave-types' },
  '/hr/staff-attendance': { path: '/hr', tab: 'staff-attendance' },
  '/hr/employees': { path: '/hr', tab: null },
  '/hr/designations': { path: '/hr', tab: 'designations' },
  '/hr/recruitment': { path: '/hr', tab: 'recruitment' },
  '/hr/recruitment/applications': { path: '/hr', tab: 'recruitment' },
  '/hr/discipline': { path: '/hr', tab: 'discipline' },
  '/compliance': { path: '/hr', tab: 'policies' },
  '/compliance/incidents': { path: '/hr', tab: 'incidents' },
  '/compliance/consent': { path: '/hr', tab: 'consent' },
  '/compliance/audit': { path: '/hr', tab: 'audit' },
  '/communications/announcements': { path: '/communications', tab: null },
  '/communications/threads': { path: '/communications', tab: 'messages' },
  '/admin/subscription': { path: '/admin', tab: 'subscription' },
  '/admin/users': { path: '/admin', tab: null },
  '/admin/roles': { path: '/admin', tab: 'roles' },
  '/academics/classes': { path: '/settings', tab: 'classes' },
  '/academics/setup': { path: '/settings', tab: 'classes' },
  '/academics/streams': { path: '/settings', tab: 'streams' },
  '/academics/subjects': { path: '/settings', tab: 'subjects' },
  '/academics/rooms': { path: '/settings', tab: 'rooms' },
  '/academics/terms': { path: '/settings', tab: 'academic-setup' },
  '/academics/departments': { path: '/settings', tab: 'departments' },
  '/academics/houses': { path: '/settings', tab: 'houses' },
  '/academics/grade-levels': { path: '/settings', tab: 'grade-levels' },
  '/academics/grading-scales': { path: '/settings', tab: 'grading' },
}

/** Hub default tabs (when URL has no ?tab=). */
export const HUB_DEFAULT_TABS: Record<string, string> = {
  '/people': 'students',
  '/finance': 'overview',
  '/hr': 'employees',
  '/operations': 'inventory',
  '/communications': 'announcements',
  '/admin': 'users',
  '/settings': 'profile',
}

export function parseNavHref(href: string): NavTarget {
  const [rawPath, queryString = ''] = href.split('?')
  const path = rawPath || '/'
  const params = new URLSearchParams(queryString)
  const tab = params.get('tab')
  const status = params.get('status')
  const staff = params.get('staff')

  const redirect = REDIRECT_TARGETS[path]
  if (redirect) {
    return {
      path: redirect.path,
      tab: redirect.tab ?? tab,
      status: redirect.status !== undefined ? redirect.status : status,
      staff: redirect.staff !== undefined ? redirect.staff : staff,
    }
  }

  return { path, tab, status, staff }
}

export function currentNavTarget(
  routePath: string,
  routeTab: unknown,
  routeStatus?: unknown,
  routeStaff?: unknown,
): NavTarget {
  const tab = typeof routeTab === 'string' && routeTab ? routeTab : null
  const status = typeof routeStatus === 'string' && routeStatus ? routeStatus : null
  const staff = typeof routeStaff === 'string' && routeStaff ? routeStaff : null
  return { path: routePath || '/', tab, status, staff }
}

export function effectiveTab(target: NavTarget): string | null {
  if (target.tab) return target.tab
  return HUB_DEFAULT_TABS[target.path] ?? null
}

export function navTargetsEqual(a: NavTarget, b: NavTarget): boolean {
  if (a.path === '/' || b.path === '/') {
    return a.path === '/' && b.path === '/'
  }
  if (a.path !== b.path) return false

  const aTab = effectiveTab(a)
  const bTab = effectiveTab(b)
  if (aTab || bTab) {
    if (aTab !== bTab) return false
  }

  // Status / staff filters are part of identity when either side declares them.
  const aStatus = a.status ?? null
  const bStatus = b.status ?? null
  if (aStatus !== bStatus) return false

  const aStaff = a.staff ?? null
  const bStaff = b.staff ?? null
  return aStaff === bStaff
}

export function isNavHrefActive(
  href: string | undefined,
  routePath: string,
  routeTab: unknown,
  siblingHrefs: Array<string | undefined> = [],
  routeStatus?: unknown,
  routeStaff?: unknown,
): boolean {
  if (!href) return false

  const target = parseNavHref(href)
  const current = currentNavTarget(routePath, routeTab, routeStatus, routeStaff)
  if (!navTargetsEqual(target, current)) return false

  // Prefer the most specific sibling (same path, more specific tab/status/staff).
  const moreSpecific = siblingHrefs.some((other) => {
    if (!other || other === href) return false
    const sibling = parseNavHref(other)
    if (!navTargetsEqual(sibling, current)) return false

    const hrefTab = parseNavHref(href).tab
    const siblingTab = sibling.tab
    if (hrefTab == null && siblingTab != null) return true

    const hrefStatus = parseNavHref(href).status ?? null
    const siblingStatus = sibling.status ?? null
    if (hrefStatus == null && siblingStatus != null) return true

    const hrefStaff = parseNavHref(href).staff ?? null
    const siblingStaff = sibling.staff ?? null
    if (hrefStaff == null && siblingStaff != null) return true

    if (href.length < other.length && sibling.path === target.path) return true
    return false
  })

  return !moreSpecific
}

/** Known non-hub page paths that must exist as real routes. */
export const DIRECT_PAGE_PATHS = [
  '/',
  '/analytics',
  '/reports',
  '/teaching',
  '/academics/exams',
  '/academics/exam-schedules',
  '/academics/grades',
  '/academics/analytics',
  '/academics/attendance',
  '/academics/certificates',
  '/academics/timetable',
  '/academics/my-timetable',
  '/academics/tests',
  '/teaching',
  '/dashboard/lms',
  '/dashboard/student',
  '/dashboard/teacher',
  '/dashboard/parent',
  '/profile',
  '/assistant',
  '/admin',
  '/settings',
  '/finance',
  '/people',
  '/hr',
  '/operations',
  '/communications',
  '/portal',
  '/portal/children',
  '/portal/hub',
  '/student',
  '/student/timetable',
  '/student/performance',
  '/student/attendance',
  '/student/exams',
  '/student/assignments',
  '/student/announcements',
  '/student/fees',
  '/enterprise',
  '/enterprise/finance',
  '/enterprise/finance/instalments',
  '/enterprise/academic',
  '/enterprise/exams',
  '/enterprise/hr',
  '/enterprise/warnings',
  '/enterprise/alumni',
  '/enterprise/campaigns',
  '/enterprise/analytics',
  '/platform',
  '/platform/licenses',
  '/platform/health',
  '/platform/operations',
  '/platform/api-clients',
  '/platform/communications',
  '/platform/documents',
  '/platform/scholarships',
  '/platform/refunds',
  '/platform/staff-tasks',
] as const
