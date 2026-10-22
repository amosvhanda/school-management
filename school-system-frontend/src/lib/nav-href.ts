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
  '/students/suspended': { path: '/people', tab: null, status: 'suspended' },
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
  '/finance/budgets': { path: '/finance', tab: 'budgets' },
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
  '/hr/staff-tasks': { path: '/hr', tab: 'staff-tasks' },
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

/** Collection paths whose detail URLs (`/students/12`) should keep the list link active. */
const DETAIL_COLLECTION_PATHS = new Set([
  '/students',
  '/teachers',
  '/guardians',
  '/hr/employees',
])

function pathsAlign(navPath: string, routePath: string): boolean {
  if (navPath === routePath) return true
  if (!DETAIL_COLLECTION_PATHS.has(navPath)) return false
  // One numeric id segment only: /students/42 — not /students/create or nested hubs.
  const prefix = `${navPath}/`
  if (!routePath.startsWith(prefix)) return false
  const rest = routePath.slice(prefix.length)
  return /^\d+$/.test(rest)
}

export function navTargetsEqual(a: NavTarget, b: NavTarget): boolean {
  if (a.path === '/' || b.path === '/') {
    return a.path === '/' && b.path === '/'
  }
  const detailVsCollection =
    a.path !== b.path
    && (pathsAlign(a.path, b.path) || pathsAlign(b.path, a.path))

  if (!detailVsCollection && a.path !== b.path) return false

  if (!detailVsCollection) {
    const aTab = effectiveTab(a)
    const bTab = effectiveTab(b)
    if (aTab || bTab) {
      if (aTab !== bTab) return false
    }
  }

  // Status / staff filters are part of identity when either side declares them.
  const aStatus = a.status ?? null
  const bStatus = b.status ?? null
  if (aStatus !== bStatus) return false

  const aStaff = a.staff ?? null
  const bStaff = b.staff ?? null
  return aStaff === bStaff
}

/** Higher score = better match when several nav links resolve to the same place. */
export function navHrefSpecificity(href: string): number {
  const rawPath = href.split('?')[0] || '/'
  const params = new URLSearchParams(href.includes('?') ? href.slice(href.indexOf('?') + 1) : '')
  let score = href.length
  if (params.has('tab')) score += 100
  if (params.has('status')) score += 50
  if (params.has('staff')) score += 50
  // Prefer the final hub path over a redirect alias (e.g. /people?tab=… over /enrollment).
  if (!REDIRECT_TARGETS[rawPath]) score += 40
  return score
}

/**
 * Whether this nav href should show as active for the current route.
 * `competitorHrefs` should be every visible sidebar href (not only siblings) so
 * duplicate destinations (e.g. Application + Admissions → /enrollment) only
 * highlight once — the most specific / first occurrence wins.
 */
export function isNavHrefActive(
  href: string | undefined,
  routePath: string,
  routeTab: unknown,
  competitorHrefs: Array<string | undefined> = [],
  routeStatus?: unknown,
  routeStaff?: unknown,
  /** Stable id when several items share the same href string. */
  linkKey?: string,
  competitorKeys?: Array<string | undefined>,
): boolean {
  if (!href) return false

  const target = parseNavHref(href)
  const current = currentNavTarget(routePath, routeTab, routeStatus, routeStaff)
  if (!navTargetsEqual(target, current)) return false

  const matches: Array<{ href: string; key: string; index: number; score: number }> = []
  competitorHrefs.forEach((other, index) => {
    if (!other) return
    if (!navTargetsEqual(parseNavHref(other), current)) return
    const key = competitorKeys?.[index] ?? `${other}#${index}`
    matches.push({
      href: other,
      key,
      index,
      score: navHrefSpecificity(other),
    })
  })

  if (matches.length === 0) return true

  matches.sort((a, b) => {
    if (b.score !== a.score) return b.score - a.score
    return a.index - b.index
  })

  const winner = matches[0]!
  const selfKey = linkKey ?? `${href}#${competitorHrefs.findIndex((h) => h === href)}`
  return winner.key === selfKey
}

/** Flatten sidebar groups into ordered href + key pairs for active-state de-dupe. */
export function flattenNavLinks(groups: Array<{ label?: string; items: Array<{
  title: string
  href?: string
  items?: Array<{ title: string; href?: string }>
}> }>): Array<{ key: string; href: string }> {
  const links: Array<{ key: string; href: string }> = []
  for (const group of groups) {
    const groupLabel = group.label ?? ''
    for (const item of group.items) {
      if (item.href) {
        links.push({ key: `${groupLabel}::${item.title}`, href: item.href })
      }
      for (const child of item.items ?? []) {
        if (child.href) {
          links.push({
            key: `${groupLabel}::${item.title}::${child.title}`,
            href: child.href,
          })
        }
      }
    }
  }
  return links
}

/** Known non-hub page paths that must exist as real routes. */
export const DIRECT_PAGE_PATHS = [
  '/',
  '/analytics',
  '/reports',
  '/workflows',
  '/teaching',
  '/students',
  '/teachers',
  '/academics/exams',
  '/academics/exam-schedules',
  '/academics/grades',
  '/academics/analytics',
  '/academics/attendance',
  '/academics/certificates',
  '/academics/timetable',
  '/academics/my-timetable',
  '/academics/holiday-programs',
  '/academics/tests',
  '/people/analytics',
  '/academics/analytics',
  '/finance/analytics',
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
