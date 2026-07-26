/**
 * Validates every sidebar href resolves to a known route, redirect, or hub tab.
 * Run: npx tsx src/lib/nav-href.audit.ts
 */
import {
  adminNavigation,
  accountsNavigation,
  examinationOfficerNavigation,
  financeNavigation,
  parentNavigation,
  platformNavigation,
  staffNavigation,
  studentNavigation,
  teacherNavigation,
} from './navigation'
import { DIRECT_PAGE_PATHS, HUB_DEFAULT_TABS, parseNavHref } from './nav-href'
import type { NavGroup, NavItem } from '@/types/navigation'

const HUB_TABS: Record<string, string[]> = {
  '/people': ['students', 'student-categories', 'teachers', 'guardians', 'enrollment'],
  '/finance': [
    'overview',
    'payments',
    'invoices',
    'fees',
    'accounting',
    'transactions',
    'payroll',
    'cash-flow',
    'aging',
    'procurement',
    'assets',
  ],
  '/hr': [
    'employees',
    'designations',
    'leave',
    'leave-types',
    'staff-attendance',
    'discipline',
    'policies',
    'incidents',
    'consent',
    'audit',
  ],
  '/operations': [
    'inventory',
    'library',
    'transport',
    'visitors',
    'events',
    'trips',
    'hostels',
    'health',
  ],
  '/communications': ['announcements', 'messages'],
  '/admin': ['users', 'roles', 'subscription'],
  '/settings': [
    'profile',
    'academic-setup',
    'grade-levels',
    'classes',
    'streams',
    'houses',
    'subjects',
    'departments',
    'subject-packages',
    'grading',
    'rooms',
    'fees',
    'student-categories',
    'currencies',
    'languages',
    'designations',
  ],
}

const knownPaths = new Set<string>([
  ...DIRECT_PAGE_PATHS,
  ...Object.keys(HUB_DEFAULT_TABS),
])

function collectHrefs(items: NavItem[], out: string[] = []): string[] {
  for (const item of items) {
    if (item.href) out.push(item.href)
    if (item.items?.length) collectHrefs(item.items, out)
  }
  return out
}

function validateGroups(label: string, groups: NavGroup[]): string[] {
  const errors: string[] = []
  const hrefs = groups.flatMap((g) => collectHrefs(g.items))

  for (const href of hrefs) {
    const target = parseNavHref(href)
    const [rawPath] = href.split('?')

    const isDirect = knownPaths.has(target.path) || knownPaths.has(rawPath)
    const hubTabs = HUB_TABS[target.path]

    if (!isDirect && !hubTabs) {
      errors.push(`[${label}] Unknown path for "${href}" → ${target.path}`)
      continue
    }

    if (hubTabs) {
      const tab = target.tab ?? HUB_DEFAULT_TABS[target.path] ?? null
      if (tab && !hubTabs.includes(tab)) {
        errors.push(`[${label}] Unknown tab for "${href}" → ${target.path}?tab=${tab}`)
      }
    }
  }

  return errors
}

const allErrors = [
  ...validateGroups('staff', staffNavigation),
  ...validateGroups('admin', adminNavigation),
  ...validateGroups('teacher', teacherNavigation),
  ...validateGroups('finance', financeNavigation),
  ...validateGroups('accounts', accountsNavigation),
  ...validateGroups('examination_officer', examinationOfficerNavigation),
  ...validateGroups('parent', parentNavigation),
  ...validateGroups('student', studentNavigation),
  ...validateGroups('platform', platformNavigation),
]

if (allErrors.length) {
  console.error(`Nav audit failed (${allErrors.length}):`)
  for (const error of allErrors) console.error(`  - ${error}`)
  process.exit(1)
}

console.log('Nav audit passed: all sidebar hrefs resolve to known routes/tabs.')
