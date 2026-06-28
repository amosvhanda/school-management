import type { NavCapability } from '@/types/navigation'

export type SectionKey =
  | 'people'
  | 'academics'
  | 'finance'
  | 'hr'
  | 'operations'
  | 'communications'

export interface SectionQuickLink {
  title: string
  description: string
  href: string
  icon: string
}

export interface SectionHubDefinition {
  key: SectionKey
  title: string
  description: string
  analyticsPath: string
  capability?: NavCapability | NavCapability[]
  quickLinks: SectionQuickLink[]
}

export const SECTION_HUBS: Record<SectionKey, SectionHubDefinition> = {
  people: {
    key: 'people',
    title: 'People analytics',
    description: 'Enrolment, staff, guardians, and admission pipeline at a glance.',
    analyticsPath: '/people/analytics',
    capability: 'canManageStudents',
    quickLinks: [
      { title: 'Students', description: 'Browse and manage learners', href: '/students', icon: 'GraduationCap' },
      { title: 'Teachers', description: 'Staff records and assignments', href: '/teachers', icon: 'Users' },
      { title: 'Guardians', description: 'Parents and contacts', href: '/guardians', icon: 'UserCheck' },
      { title: 'Enrollment', description: 'Applications and intake', href: '/enrollment', icon: 'ClipboardList' },
      { title: 'Add student', description: 'Register a new learner', href: '/students?create=1', icon: 'UserPlus' },
    ],
  },
  academics: {
    key: 'academics',
    title: 'Academics analytics',
    description: 'Attendance, assessments, exams, and class performance.',
    analyticsPath: '/academics/analytics',
    capability: ['canManageTeachers', 'canManageStudents', 'canEnterExamResults'],
    quickLinks: [
      { title: 'Attendance register', description: 'Mark daily class attendance', href: '/academics/attendance', icon: 'ClipboardCheck' },
      { title: 'Exams', description: 'Enter marks and review results', href: '/academics/exams', icon: 'FileText' },
      { title: 'Gradebook', description: 'Enter and review marks', href: '/academics/grades', icon: 'NotebookPen' },
      { title: 'Classes', description: 'Class and grade setup', href: '/academics/setup', icon: 'BookOpen' },
      { title: 'Timetable', description: 'Weekly class schedule', href: '/academics/timetable', icon: 'CalendarDays' },
      { title: 'Terms', description: 'Academic calendar terms', href: '/academics/terms', icon: 'Calendar' },
    ],
  },
  finance: {
    key: 'finance',
    title: 'Finance analytics',
    description: 'Collections, outstanding fees, invoices, and payroll.',
    analyticsPath: '/finance/analytics',
    capability: 'canManageFinance',
    quickLinks: [
      { title: 'Finance overview', description: 'Daily collections snapshot', href: '/finance', icon: 'Wallet' },
      { title: 'Record payment', description: 'Log a fee collection', href: '/finance/payments?create=1', icon: 'CreditCard' },
      { title: 'Invoices', description: 'Bill students', href: '/finance/invoices', icon: 'Receipt' },
      { title: 'Fee structures', description: 'Configure fees by class', href: '/finance/fees', icon: 'Tags' },
      { title: 'Transactions', description: 'Ledger activity', href: '/finance/transactions', icon: 'ArrowLeftRight' },
      { title: 'Aging report', description: 'Outstanding by age', href: '/finance/reports', icon: 'BarChart3' },
    ],
  },
  hr: {
    key: 'hr',
    title: 'HR & compliance analytics',
    description: 'Leave, discipline, policies, and audit activity.',
    analyticsPath: '/hr/analytics',
    capability: 'canManageTeachers',
    quickLinks: [
      { title: 'Leave requests', description: 'Staff leave queue', href: '/hr/leave', icon: 'Palmtree' },
      { title: 'Discipline', description: 'Student conduct records', href: '/hr/discipline', icon: 'ShieldAlert' },
      { title: 'Policies', description: 'Compliance policies', href: '/compliance', icon: 'Scale' },
      { title: 'Audit trail', description: 'Who did what across the system', href: '/compliance/audit', icon: 'ScrollText' },
      { title: 'Workflows', description: 'Pending approvals', href: '/workflows', icon: 'GitBranch' },
    ],
  },
  operations: {
    key: 'operations',
    title: 'Operations analytics',
    description: 'Inventory, library, transport, visitors, and campus events.',
    analyticsPath: '/operations/analytics',
    capability: 'canManageTeachers',
    quickLinks: [
      { title: 'Inventory', description: 'Stock and supplies', href: '/operations/inventory', icon: 'Package' },
      { title: 'Library', description: 'Books and lending', href: '/operations/library', icon: 'Library' },
      { title: 'Transport', description: 'Routes and vehicles', href: '/operations/transport', icon: 'Bus' },
      { title: 'Visitors', description: 'Sign-in register', href: '/operations/visitors', icon: 'UserCheck' },
      { title: 'Events', description: 'School calendar events', href: '/operations/events', icon: 'CalendarHeart' },
      { title: 'Procurement', description: 'Purchase requests', href: '/operations/procurement', icon: 'ShoppingCart' },
    ],
  },
  communications: {
    key: 'communications',
    title: 'Communications analytics',
    description: 'Announcements, messaging threads, and parent engagement.',
    analyticsPath: '/communications/analytics',
    capability: 'isStaff',
    quickLinks: [
      { title: 'Announcements', description: 'Broadcast to school', href: '/communications/announcements', icon: 'Megaphone' },
      { title: 'Messages', description: 'Staff and parent inbox', href: '/communications/threads', icon: 'MessageSquare' },
      { title: 'Assistant', description: 'AI school assistant', href: '/assistant', icon: 'Bot' },
      { title: 'Reports', description: 'Download school reports', href: '/reports', icon: 'FileBarChart' },
    ],
  },
}

export function getSectionHub(key: string | undefined): SectionHubDefinition | null {
  if (!key || !(key in SECTION_HUBS)) return null
  return SECTION_HUBS[key as SectionKey]
}
