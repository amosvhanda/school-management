import type { NavGroup } from '@/types/navigation'

export const staffNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard' },
      { title: 'School analytics', href: '/analytics', icon: 'BarChart3', capability: 'canManageTeachers' },
      { title: 'Workflows', href: '/workflows', icon: 'GitBranch', capability: 'canManageTeachers' },
    ],
  },
  {
    label: 'People',
    items: [
      { title: 'Analytics', href: '/people/analytics', icon: 'BarChart3', capability: 'canManageStudents' },
      { title: 'Students', href: '/students', icon: 'GraduationCap', capability: 'canManageStudents' },
      { title: 'Teachers', href: '/teachers', icon: 'Users', capability: 'canManageTeachers' },
      { title: 'Guardians', href: '/guardians', icon: 'UserCheck', capability: 'canManageStudents' },
      { title: 'Enrollment', href: '/enrollment', icon: 'ClipboardList', capability: 'canManageStudents' },
    ],
  },
  {
    label: 'Academics',
    items: [
      { title: 'Analytics', href: '/academics/analytics', icon: 'BarChart3', capability: ['canManageTeachers', 'canManageStudents', 'canEnterExamResults'] },
      { title: 'Classes', href: '/academics/setup', icon: 'BookOpen', capability: 'canManageTeachers' },
      { title: 'Subjects', href: '/academics/subjects', icon: 'BookMarked', capability: 'canManageTeachers' },
      { title: 'Terms', href: '/academics/terms', icon: 'Calendar', capability: 'canManageTeachers' },
      { title: 'Gradebook', href: '/academics/grades', icon: 'NotebookPen', capability: 'canManageExaminations' },
      { title: 'Attendance', href: '/academics/attendance', icon: 'ClipboardCheck', capability: 'canManageStudents' },
      { title: 'Exams', href: '/academics/exams', icon: 'FileText', capability: ['canManageExaminations', 'canEnterExamResults'] },
      { title: 'Timetable', href: '/academics/timetable', icon: 'CalendarDays', capability: 'canManageTeachers' },
    ],
  },
  {
    label: 'Finance',
    items: [
      { title: 'Analytics', href: '/finance/analytics', icon: 'BarChart3', capability: 'canManageFinance' },
      { title: 'Overview', href: '/finance', icon: 'Wallet', capability: 'canManageFinance' },
      { title: 'Payments', href: '/finance/payments', icon: 'CreditCard', capability: 'canManageFinance' },
      { title: 'Invoices', href: '/finance/invoices', icon: 'Receipt', capability: 'canManageFinance' },
      { title: 'Fee Structures', href: '/finance/fees', icon: 'Tags', capability: 'canManageFinance' },
      { title: 'Transactions', href: '/finance/transactions', icon: 'ArrowLeftRight', capability: 'canManageFinance' },
      { title: 'Payroll', href: '/finance/payroll', icon: 'Banknote', capability: 'canManageFinance' },
      { title: 'Aging report', href: '/finance/reports', icon: 'LineChart', capability: 'canManageFinance' },
    ],
  },
  {
    label: 'HR & Compliance',
    items: [
      { title: 'Analytics', href: '/hr/analytics', icon: 'BarChart3', capability: 'canManageTeachers' },
      { title: 'Leave requests', href: '/hr/leave', icon: 'Palmtree', capability: 'canManageTeachers' },
      { title: 'Discipline', href: '/hr/discipline', icon: 'ShieldAlert', capability: 'canManageStudents' },
      { title: 'Policies', href: '/compliance', icon: 'Scale', capability: 'canManageTeachers' },
      { title: 'Audit trail', href: '/compliance/audit', icon: 'ScrollText', capability: 'canViewAuditLogs' },
    ],
  },
  {
    label: 'Communications',
    items: [
      { title: 'Analytics', href: '/communications/analytics', icon: 'BarChart3', capability: 'isStaff' },
      { title: 'Announcements', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff' },
      { title: 'Messages', href: '/communications/threads', icon: 'MessageSquare', capability: 'isStaff' },
    ],
  },
  {
    label: 'Operations',
    items: [
      { title: 'Analytics', href: '/operations/analytics', icon: 'BarChart3', capability: 'canManageTeachers' },
      { title: 'Inventory', href: '/operations/inventory', icon: 'Package', capability: 'canManageTeachers' },
      { title: 'Library', href: '/operations/library', icon: 'Library', capability: 'canManageTeachers' },
      { title: 'Transport', href: '/operations/transport', icon: 'Bus', capability: 'canManageTeachers' },
      { title: 'Visitors', href: '/operations/visitors', icon: 'UserCheck', capability: 'canManageTeachers' },
      { title: 'Events', href: '/operations/events', icon: 'CalendarHeart', capability: 'canManageTeachers' },
    ],
  },
  {
    label: 'Administration',
    items: [
      { title: 'Users', href: '/admin/users', icon: 'UserCog', capability: 'canManageTeachers' },
      { title: 'Roles', href: '/admin/roles', icon: 'KeyRound', capability: 'canManageTeachers' },
      { title: 'Settings', href: '/settings', icon: 'Settings', capability: 'canManageTeachers' },
    ],
  },
]

export const parentNavigation: NavGroup[] = [
  {
    label: 'Portal',
    items: [
      { title: 'Home', href: '/portal', icon: 'Home' },
      { title: 'My Children', href: '/portal/children', icon: 'Users', roles: ['parent'] },
      { title: 'Messages', href: '/portal/messages', icon: 'MessageSquare', roles: ['parent'] },
      { title: 'Announcements', href: '/portal/announcements', icon: 'Megaphone', roles: ['parent'] },
      { title: 'Consent forms', href: '/portal/consent', icon: 'FileCheck', roles: ['parent'] },
      { title: 'Notifications', href: '/portal/notifications', icon: 'Bell', roles: ['parent'] },
    ],
  },
]

export const platformNavigation: NavGroup[] = [
  {
    label: 'Platform',
    items: [
      { title: 'Dashboard', href: '/platform', icon: 'LayoutDashboard', roles: ['super_admin'] },
      { title: 'Licenses', href: '/platform/licenses', icon: 'Key', roles: ['super_admin'] },
      { title: 'Health', href: '/platform/health', icon: 'Activity', roles: ['super_admin'] },
      { title: 'Operations', href: '/platform/operations', icon: 'Server', roles: ['super_admin'] },
      { title: 'API clients', href: '/platform/api-clients', icon: 'Plug', roles: ['super_admin'] },
    ],
  },
]
