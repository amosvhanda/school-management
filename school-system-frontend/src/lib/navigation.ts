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
      { title: 'Classes', href: '/academics/classes', icon: 'BookOpen', capability: 'canManageTeachers' },
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
      { title: 'Cash flow 360', href: '/finance/cash-flow', icon: 'Scale', capability: 'canManageFinance' },
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
      { title: 'Analytics', href: '/operations/analytics', icon: 'BarChart3', capability: ['canManageInventory', 'canManageLibrary', 'canManageTransport', 'canManageReception'] },
      { title: 'Inventory', href: '/operations/inventory', icon: 'Package', capability: 'canManageInventory' },
      { title: 'Inventory sales', href: '/operations/inventory/sales', icon: 'ShoppingBag', capability: 'canManageInventory' },
      { title: 'Library', href: '/operations/library', icon: 'Library', capability: 'canManageLibrary' },
      { title: 'Transport', href: '/operations/transport', icon: 'Bus', capability: 'canManageTransport' },
      { title: 'Drivers', href: '/operations/transport/drivers', icon: 'IdCard', capability: 'canManageTransport' },
      { title: 'Routes', href: '/operations/transport/routes', icon: 'Map', capability: 'canManageTransport' },
      { title: 'Visitors', href: '/operations/visitors', icon: 'UserCheck', capability: 'canManageReception' },
      { title: 'Events', href: '/operations/events', icon: 'CalendarHeart', capability: 'canManageTeachers' },
      { title: 'School trips', href: '/operations/school-trips', icon: 'Bus', capability: 'canManageTeachers' },
      { title: 'Hostels', href: '/operations/hostels', icon: 'Building2', capability: 'canManageTeachers' },
      { title: 'Clinic visits', href: '/operations/health', icon: 'HeartPulse', capability: 'canManageTeachers' },
    ],
  },
  {
    label: 'Tools',
    items: [
      { title: 'Assistant', href: '/assistant', icon: 'Bot', capability: 'isStaff' },
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

export const adminNavigation: NavGroup[] = staffNavigation.map((group) => ({
  ...group,
  items: group.items.map((item) => ({
    ...item,
    roles: ['admin'],
  })),
}))

export const teacherNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['teacher'] },
      {
        title: 'Academics analytics',
        href: '/academics/analytics',
        icon: 'BarChart3',
        capability: ['canManageTeachers', 'canManageStudents', 'canEnterExamResults'],
        roles: ['teacher'],
      },
    ],
  },
  {
    label: 'Teaching',
    items: [
      { title: 'My timetable', href: '/academics/my-timetable', icon: 'CalendarDays', roles: ['teacher'] },
      { title: 'Attendance', href: '/academics/attendance', icon: 'ClipboardCheck', capability: 'canManageStudents', roles: ['teacher'] },
      { title: 'Exams', href: '/academics/exams', icon: 'FileText', capability: ['canManageExaminations', 'canEnterExamResults'], roles: ['teacher'] },
      { title: 'Students', href: '/students', icon: 'GraduationCap', capability: 'canManageStudents', roles: ['teacher'] },
      { title: 'Guardians', href: '/guardians', icon: 'UserCheck', capability: 'canManageStudents', roles: ['teacher'] },
    ],
  },
  {
    label: 'Communications',
    items: [
      { title: 'Messages', href: '/communications/threads', icon: 'MessageSquare', capability: 'isStaff', roles: ['teacher'] },
      { title: 'Announcements', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['teacher'] },
      { title: 'Analytics', href: '/communications/analytics', icon: 'BarChart3', capability: 'isStaff', roles: ['teacher'] },
    ],
  },
  {
    label: 'Tools',
    items: [
      { title: 'Assistant', href: '/assistant', icon: 'Bot', capability: 'isStaff', roles: ['teacher'] },
    ],
  },
]

export const financeNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['finance'] },
      { title: 'Finance overview', href: '/finance', icon: 'Wallet', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Finance analytics', href: '/finance/analytics', icon: 'BarChart3', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Cash flow 360', href: '/finance/cash-flow', icon: 'Scale', capability: 'canManageFinance', roles: ['finance'] },
    ],
  },
  {
    label: 'Billing',
    items: [
      { title: 'Payments', href: '/finance/payments', icon: 'CreditCard', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Invoices', href: '/finance/invoices', icon: 'Receipt', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Fee structures', href: '/finance/fees', icon: 'Tags', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Fee categories', href: '/finance/fee-categories', icon: 'FolderTree', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Aging report', href: '/finance/reports', icon: 'LineChart', capability: 'canManageFinance', roles: ['finance'] },
    ],
  },
  {
    label: 'Operations',
    items: [
      { title: 'Transactions', href: '/finance/transactions', icon: 'ArrowLeftRight', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Payroll', href: '/finance/payroll', icon: 'Banknote', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Spend requests', href: '/operations/procurement', icon: 'ShoppingCart', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Vendors', href: '/operations/procurement/vendors', icon: 'Truck', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Assets', href: '/operations/assets', icon: 'HardDrive', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Enterprise finance', href: '/enterprise/finance', icon: 'Landmark', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Instalment plans', href: '/enterprise/finance/instalments', icon: 'CalendarClock', capability: 'canManageFinance', roles: ['finance'] },
    ],
  },
]

export const accountsNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['accounts'] },
      { title: 'Finance analytics', href: '/finance/analytics', icon: 'BarChart3', capability: 'canManageFinance', roles: ['accounts'] },
    ],
  },
  {
    label: 'Collections',
    items: [
      { title: 'Finance overview', href: '/finance', icon: 'Wallet', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Cash flow 360', href: '/finance/cash-flow', icon: 'Scale', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Payments', href: '/finance/payments', icon: 'CreditCard', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Invoices', href: '/finance/invoices', icon: 'Receipt', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Fee structures', href: '/finance/fees', icon: 'Tags', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Fee categories', href: '/finance/fee-categories', icon: 'FolderTree', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Aging report', href: '/finance/reports', icon: 'LineChart', capability: 'canManageFinance', roles: ['accounts'] },
    ],
  },
  {
    label: 'Accounting',
    items: [
      { title: 'Transactions', href: '/finance/transactions', icon: 'ArrowLeftRight', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Payroll', href: '/finance/payroll', icon: 'Banknote', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Spend requests', href: '/operations/procurement', icon: 'ShoppingCart', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Vendors', href: '/operations/procurement/vendors', icon: 'Truck', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Assets', href: '/operations/assets', icon: 'HardDrive', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Instalment plans', href: '/enterprise/finance/instalments', icon: 'CalendarClock', capability: 'canManageFinance', roles: ['accounts'] },
    ],
  },
  {
    label: 'Communication & Control',
    items: [
      { title: 'Messages', href: '/communications/threads', icon: 'MessageSquare', capability: 'isStaff', roles: ['accounts'] },
      { title: 'Announcements', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['accounts'] },
      { title: 'Audit trail', href: '/compliance/audit', icon: 'ScrollText', capability: 'canViewAuditLogs', roles: ['accounts'] },
      { title: 'Assistant', href: '/assistant', icon: 'Bot', capability: 'isStaff', roles: ['accounts'] },
    ],
  },
]

export const examinationOfficerNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['examination_officer'] },
      {
        title: 'Academics analytics',
        href: '/academics/analytics',
        icon: 'BarChart3',
        capability: ['canManageTeachers', 'canManageStudents', 'canManageExaminations'],
        roles: ['examination_officer'],
      },
      {
        title: 'Reports',
        href: '/reports',
        icon: 'FileBarChart',
        capability: 'canManageTeachers',
        roles: ['examination_officer'],
      },
    ],
  },
  {
    label: 'Examinations',
    items: [
      { title: 'Exams', href: '/academics/exams', icon: 'FileText', capability: 'canManageExaminations', roles: ['examination_officer'] },
      { title: 'Gradebook', href: '/academics/grades', icon: 'BookOpen', capability: 'canManageExaminations', roles: ['examination_officer'] },
      { title: 'Class tests', href: '/academics/tests', icon: 'NotebookPen', capability: 'canManageExaminations', roles: ['examination_officer'] },
      { title: 'Attendance', href: '/academics/attendance', icon: 'ClipboardCheck', capability: 'canManageStudents', roles: ['examination_officer'] },
      { title: 'Students', href: '/students', icon: 'GraduationCap', capability: 'canManageStudents', roles: ['examination_officer'] },
    ],
  },
  {
    label: 'Communications',
    items: [
      { title: 'Messages', href: '/communications/threads', icon: 'MessageSquare', capability: 'isStaff', roles: ['examination_officer'] },
      { title: 'Announcements', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['examination_officer'] },
      { title: 'Assistant', href: '/assistant', icon: 'Bot', capability: 'isStaff', roles: ['examination_officer'] },
    ],
  },
]

export const parentNavigation: NavGroup[] = [
  {
    label: 'Portal',
    items: [
      { title: 'Home', href: '/portal', icon: 'Home', capability: 'isParent', roles: ['parent'] },
      { title: 'My Children', href: '/portal/children', icon: 'Users', capability: 'isParent', roles: ['parent'] },
      { title: 'Messages', href: '/portal/messages', icon: 'MessageSquare', capability: 'isParent', roles: ['parent'] },
      { title: 'Announcements', href: '/portal/announcements', icon: 'Megaphone', capability: 'isParent', roles: ['parent'] },
      { title: 'Consent forms', href: '/portal/consent', icon: 'FileCheck', capability: 'isParent', roles: ['parent'] },
      { title: 'School store', href: '/portal/store', icon: 'ShoppingBag', capability: 'isParent', roles: ['parent'] },
      { title: 'School trips', href: '/portal/trips', icon: 'Bus', capability: 'isParent', roles: ['parent'] },
      { title: 'Notifications', href: '/portal/notifications', icon: 'Bell', capability: 'isParent', roles: ['parent'] },
    ],
  },
]

export const studentNavigation: NavGroup[] = [
  {
    label: 'Student portal',
    items: [
      { title: 'Dashboard', href: '/student', icon: 'Home', roles: ['student'] },
      { title: 'Timetable', href: '/student/timetable', icon: 'CalendarDays', roles: ['student'] },
      { title: 'Performance', href: '/student/performance', icon: 'BarChart3', roles: ['student'] },
      { title: 'Attendance', href: '/student/attendance', icon: 'ClipboardCheck', roles: ['student'] },
      { title: 'Exams', href: '/student/exams', icon: 'FileText', roles: ['student'] },
      { title: 'Fees', href: '/student/fees', icon: 'Wallet', roles: ['student'] },
    ],
  },
]

export const platformNavigation: NavGroup[] = [
  {
    label: 'Super Admin Portal',
    items: [
      { title: 'Dashboard', href: '/platform', icon: 'LayoutDashboard', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'Licenses', href: '/platform/licenses', icon: 'Key', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'Health', href: '/platform/health', icon: 'Activity', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'Operations', href: '/platform/operations', icon: 'Server', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'API clients', href: '/platform/api-clients', icon: 'Plug', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'Communications', href: '/platform/communications', icon: 'Megaphone', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'Signable documents', href: '/platform/documents', icon: 'FileText', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'Scholarships', href: '/platform/scholarships', icon: 'Award', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'Refunds', href: '/platform/refunds', icon: 'RotateCcw', capability: 'isSuperAdmin', roles: ['super_admin'] },
      { title: 'Staff tasks', href: '/platform/staff-tasks', icon: 'ListTodo', capability: 'isSuperAdmin', roles: ['super_admin'] },
    ],
  },
]
