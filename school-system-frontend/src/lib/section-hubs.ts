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
  capability?: NavCapability | NavCapability[]
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
      { title: 'Students', description: 'Browse and manage learners', href: '/people?tab=students', icon: 'GraduationCap', capability: 'canManageStudents' },
      { title: 'Teachers', description: 'Staff records and assignments', href: '/people?tab=teachers', icon: 'Users', capability: 'canManageTeachers' },
      { title: 'Guardians', description: 'Parents and contacts', href: '/people?tab=guardians', icon: 'UserCheck', capability: 'canManageTeachers' },
      { title: 'Enrollment', description: 'Applications and intake', href: '/people?tab=enrollment', icon: 'ClipboardList', capability: 'canManageTeachers' },
      { title: 'Add student', description: 'Register a new learner', href: '/people?tab=students&create=1', icon: 'UserPlus', capability: 'canManageStudents' },
    ],
  },
  academics: {
    key: 'academics',
    title: 'Academics analytics',
    description: 'Attendance, assessments, exams, and class performance.',
    analyticsPath: '/academics/analytics',
    capability: ['canManageTeachers', 'canManageStudents', 'canEnterExamResults'],
    quickLinks: [
      { title: 'Attendance register', description: 'Mark daily class attendance', href: '/academics/attendance', icon: 'ClipboardCheck', capability: ['canManageStudents', 'canManageTeachers'] },
      { title: 'Exams', description: 'Enter marks and review results', href: '/academics/exams', icon: 'FileText', capability: ['canManageExaminations', 'canEnterExamResults'] },
      { title: 'Gradebook', description: 'Enter and review marks', href: '/academics/grades', icon: 'NotebookPen', capability: ['canManageTeachers', 'canEnterExamResults'] },
      { title: 'School Setup', description: 'Classes, grades, subjects, rooms, and terms', href: '/settings', icon: 'Settings', capability: 'canManageTeachers' },
      { title: 'Grade levels', description: 'Form / Grade year levels', href: '/settings?tab=grade-levels', icon: 'Layers', capability: 'canManageTeachers' },
      { title: 'Streams', description: 'Sciences, Arts, and other streams', href: '/settings?tab=streams', icon: 'GitBranch', capability: 'canManageTeachers' },
      { title: 'Houses', description: 'Pastoral house groups', href: '/settings?tab=houses', icon: 'Home', capability: 'canManageTeachers' },
      { title: 'Subject packages', description: 'Subjects by grade and stream', href: '/settings?tab=subject-packages', icon: 'Package', capability: 'canManageTeachers' },
      { title: 'Timetable', description: 'Weekly class schedule', href: '/academics/timetable', icon: 'CalendarDays', capability: 'canManageTeachers' },
    ],
  },
  finance: {
    key: 'finance',
    title: 'Finance analytics',
    description: 'Collections, outstanding fees, invoices, and payroll.',
    analyticsPath: '/finance/analytics',
    capability: 'canManageFinance',
    quickLinks: [
      { title: 'Finance workspace', description: 'Billing, payroll, and ledger', href: '/finance', icon: 'Wallet' },
      { title: 'Cash flow', description: 'Money in vs money out', href: '/finance?tab=cash-flow', icon: 'Scale' },
      { title: 'Record payment', description: 'Log a fee collection', href: '/finance?tab=payments&create=1', icon: 'CreditCard' },
      { title: 'Invoices', description: 'Bill students', href: '/finance?tab=invoices', icon: 'Receipt' },
      { title: 'Fee structures', description: 'Configure fees by class', href: '/finance?tab=fees', icon: 'Tags' },
      { title: 'Transactions', description: 'Ledger activity', href: '/finance?tab=transactions', icon: 'ArrowLeftRight' },
      { title: 'Aging report', description: 'Outstanding by age', href: '/finance?tab=aging', icon: 'BarChart3' },
    ],
  },
  hr: {
    key: 'hr',
    title: 'HR & compliance analytics',
    description: 'Leave, discipline, policies, and audit activity.',
    analyticsPath: '/hr/analytics',
    capability: 'canManageTeachers',
    quickLinks: [
      { title: 'Leave requests', description: 'Staff leave queue', href: '/hr?tab=leave', icon: 'Palmtree', capability: 'canManageTeachers' },
      { title: 'Discipline', description: 'Student conduct records', href: '/hr?tab=discipline', icon: 'ShieldAlert', capability: 'canManageStudents' },
      { title: 'Policies', description: 'Compliance policies', href: '/compliance', icon: 'Scale', capability: 'canManageTeachers' },
      { title: 'Incidents', description: 'Compliance incidents', href: '/hr?tab=incidents', icon: 'AlertTriangle', capability: 'canManageTeachers' },
      { title: 'Consent forms', description: 'Parent consent management', href: '/hr?tab=consent', icon: 'FileCheck', capability: 'canManageTeachers' },
      { title: 'Audit trail', description: 'Who did what across the system', href: '/hr?tab=audit', icon: 'ScrollText', capability: 'canViewAuditLogs' },
      { title: 'Workflows', description: 'Pending approvals', href: '/workflows', icon: 'GitBranch', capability: 'canManageTeachers' },
    ],
  },
  operations: {
    key: 'operations',
    title: 'Operations analytics',
    description: 'Inventory, library, transport, visitors, and campus events.',
    analyticsPath: '/operations/analytics',
    capability: ['canManageInventory', 'canManageLibrary', 'canManageTransport', 'canManageReception'],
    quickLinks: [
      { title: 'Inventory', description: 'Stock and sales', href: '/operations?tab=inventory', icon: 'Package', capability: 'canManageInventory' },
      { title: 'Library', description: 'Books and lending', href: '/operations?tab=library', icon: 'Library', capability: 'canManageLibrary' },
      { title: 'Transport', description: 'Vehicles, drivers, and routes', href: '/operations?tab=transport', icon: 'Bus', capability: 'canManageTransport' },
      { title: 'Visitors', description: 'Sign-in register', href: '/operations?tab=visitors', icon: 'UserCheck', capability: 'canManageReception' },
      { title: 'Events', description: 'School calendar events', href: '/operations?tab=events', icon: 'CalendarHeart', capability: 'canManageTeachers' },
      { title: 'School trips', description: 'Excursions and parent registration', href: '/operations?tab=trips', icon: 'Bus', capability: 'canManageTeachers' },
      { title: 'Hostels', description: 'Boarding houses and rooms', href: '/operations?tab=hostels', icon: 'Building2', capability: 'canManageTeachers' },
      { title: 'Clinic visits', description: 'Student health records', href: '/operations?tab=health', icon: 'HeartPulse', capability: 'canManageTeachers' },
      { title: 'Spend requests', description: 'Request → approve → pay', href: '/finance?tab=procurement', icon: 'ShoppingCart', capability: 'canManageFinance' },
      { title: 'Assets', description: 'School asset register', href: '/finance?tab=assets', icon: 'HardDrive', capability: 'canManageFinance' },
    ],
  },
  communications: {
    key: 'communications',
    title: 'Communications analytics',
    description: 'Announcements, messaging threads, and parent engagement.',
    analyticsPath: '/communications/analytics',
    capability: 'isStaff',
    quickLinks: [
      { title: 'Announcements', description: 'Broadcast to school', href: '/communications?tab=announcements', icon: 'Megaphone', capability: 'isStaff' },
      { title: 'Messages', description: 'Staff and parent inbox', href: '/communications?tab=messages', icon: 'MessageSquare', capability: 'isStaff' },
      { title: 'Assistant', description: 'AI school assistant', href: '/assistant', icon: 'Bot', capability: 'isStaff' },
      { title: 'Reports', description: 'Download school reports', href: '/reports', icon: 'FileBarChart', capability: 'canManageTeachers' },
    ],
  },
}

export function getSectionHub(key: string | undefined): SectionHubDefinition | null {
  if (!key || !(key in SECTION_HUBS)) return null
  return SECTION_HUBS[key as SectionKey]
}
