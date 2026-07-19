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
      { title: 'Students', description: 'Browse and manage learners', href: '/students', icon: 'GraduationCap', capability: 'canManageStudents' },
      { title: 'Teachers', description: 'Staff records and assignments', href: '/teachers', icon: 'Users', capability: 'canManageTeachers' },
      { title: 'Guardians', description: 'Parents and contacts', href: '/guardians', icon: 'UserCheck', capability: 'canManageStudents' },
      { title: 'Enrollment', description: 'Applications and intake', href: '/enrollment', icon: 'ClipboardList', capability: 'canManageStudents' },
      { title: 'Add student', description: 'Register a new learner', href: '/students?create=1', icon: 'UserPlus', capability: 'canManageStudents' },
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
      { title: 'Classes', description: 'Class and grade setup', href: '/academics/classes', icon: 'BookOpen', capability: 'canManageTeachers' },
      { title: 'Streams', description: 'Sciences, Arts, and other streams', href: '/academics/streams', icon: 'GitBranch', capability: 'canManageTeachers' },
      { title: 'Houses', description: 'Pastoral house groups', href: '/academics/houses', icon: 'Home', capability: 'canManageTeachers' },
      { title: 'Subject packages', description: 'Subjects by grade and stream', href: '/academics/subject-packages', icon: 'Layers', capability: 'canManageTeachers' },
      { title: 'Timetable', description: 'Weekly class schedule', href: '/academics/timetable', icon: 'CalendarDays', capability: 'canManageTeachers' },
      { title: 'Terms', description: 'Academic calendar terms', href: '/academics/terms', icon: 'Calendar', capability: 'canManageTeachers' },
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
    capability: ['canManageInventory', 'canManageLibrary', 'canManageTransport', 'canManageReception'],
    quickLinks: [
      { title: 'Inventory', description: 'Stock and supplies', href: '/operations/inventory', icon: 'Package', capability: 'canManageInventory' },
      { title: 'Inventory sales', description: 'Sales to students', href: '/operations/inventory/sales', icon: 'ShoppingBag', capability: 'canManageInventory' },
      { title: 'Library', description: 'Books and lending', href: '/operations/library', icon: 'Library', capability: 'canManageLibrary' },
      { title: 'Transport', description: 'Vehicles, drivers, and routes', href: '/operations/transport', icon: 'Bus', capability: 'canManageTransport' },
      { title: 'Drivers', description: 'Transport drivers', href: '/operations/transport/drivers', icon: 'IdCard', capability: 'canManageTransport' },
      { title: 'Routes', description: 'Pickup routes', href: '/operations/transport/routes', icon: 'Map', capability: 'canManageTransport' },
      { title: 'Visitors', description: 'Sign-in register', href: '/operations/visitors', icon: 'UserCheck', capability: 'canManageReception' },
      { title: 'Events', description: 'School calendar events', href: '/operations/events', icon: 'CalendarHeart', capability: 'canManageTeachers' },
      { title: 'Hostels', description: 'Boarding houses and rooms', href: '/operations/hostels', icon: 'Building2', capability: 'canManageTeachers' },
      { title: 'Clinic visits', description: 'Student health records', href: '/operations/health', icon: 'HeartPulse', capability: 'canManageTeachers' },
      { title: 'Procurement', description: 'Purchase requests', href: '/operations/procurement', icon: 'ShoppingCart', capability: 'canManageFinance' },
      { title: 'Assets', description: 'School asset register', href: '/operations/assets', icon: 'HardDrive', capability: 'canManageFinance' },
    ],
  },
  communications: {
    key: 'communications',
    title: 'Communications analytics',
    description: 'Announcements, messaging threads, and parent engagement.',
    analyticsPath: '/communications/analytics',
    capability: 'isStaff',
    quickLinks: [
      { title: 'Announcements', description: 'Broadcast to school', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff' },
      { title: 'Messages', description: 'Staff and parent inbox', href: '/communications/threads', icon: 'MessageSquare', capability: 'isStaff' },
      { title: 'Assistant', description: 'AI school assistant', href: '/assistant', icon: 'Bot', capability: 'isStaff' },
      { title: 'Reports', description: 'Download school reports', href: '/reports', icon: 'FileBarChart', capability: 'canManageTeachers' },
    ],
  },
}

export function getSectionHub(key: string | undefined): SectionHubDefinition | null {
  if (!key || !(key in SECTION_HUBS)) return null
  return SECTION_HUBS[key as SectionKey]
}
