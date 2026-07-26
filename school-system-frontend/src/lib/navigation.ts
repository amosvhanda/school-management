import type { NavGroup } from '@/types/navigation'

export const staffNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard' },
      { title: 'School analytics', href: '/analytics', icon: 'BarChart3', capability: 'canManageTeachers' },
      { title: 'Reports', href: '/reports', icon: 'FileBarChart', capability: 'canManageTeachers' },
      { title: 'Workflows', href: '/workflows', icon: 'GitBranch', capability: 'canManageTeachers' },
    ],
  },
  {
    label: 'People',
    items: [
      { title: 'People', href: '/people', icon: 'Users', capability: ['canManageStudents', 'canManageTeachers'] },
    ],
  },
  {
    label: 'Academics',
    items: [
      { title: 'Analytics', href: '/academics/analytics', icon: 'BarChart3', capability: ['canManageTeachers', 'canManageStudents', 'canEnterExamResults'] },
      { title: 'Gradebook', href: '/academics/grades', icon: 'NotebookPen', capability: ['canManageExaminations', 'canEnterExamResults'] },
      { title: 'Attendance', href: '/academics/attendance', icon: 'ClipboardCheck', capability: 'canManageStudents' },
      { title: 'Exams', href: '/academics/exams', icon: 'FileText', capability: ['canManageExaminations', 'canEnterExamResults'] },
      { title: 'Exam schedule', href: '/academics/exam-schedules', icon: 'CalendarClock', capability: 'canManageExaminations' },
      { title: 'Certificates', href: '/academics/certificates', icon: 'Award', capability: 'canManageTeachers' },
      { title: 'Timetable', href: '/academics/timetable', icon: 'CalendarDays', capability: 'canManageTeachers' },
    ],
  },
  {
    label: 'Finance',
    items: [
      { title: 'Finance', href: '/finance', icon: 'Wallet', capability: 'canManageFinance' },
    ],
  },
  {
    label: 'HR & Compliance',
    items: [
      { title: 'HR & Compliance', href: '/hr', icon: 'Shield', capability: ['canManageTeachers', 'canManageStudents', 'canViewAuditLogs'] },
    ],
  },
  {
    label: 'Communications',
    items: [
      { title: 'Communications', href: '/communications', icon: 'Megaphone', capability: 'isStaff' },
    ],
  },
  {
    label: 'Operations',
    items: [
      { title: 'Operations', href: '/operations', icon: 'Package', capability: ['canManageInventory', 'canManageLibrary', 'canManageTransport', 'canManageReception', 'canManageTeachers'] },
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
      { title: 'Users & roles', href: '/admin', icon: 'UserCog', capability: 'canManageTeachers' },
      { title: 'School Setup', href: '/settings', icon: 'Settings', capability: 'canManageTeachers' },
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
      { title: 'My profile', href: '/profile', icon: 'UserCog', capability: 'isStaff', roles: ['teacher'] },
    ],
  },
  {
    label: 'Classroom',
    items: [
      { title: 'Teaching', href: '/teaching', icon: 'BookOpen', capability: 'isStaff', roles: ['teacher'] },
      { title: 'Attendance', href: '/academics/attendance', icon: 'ClipboardCheck', capability: 'canManageStudents', roles: ['teacher'] },
      { title: 'Gradebook', href: '/academics/grades', icon: 'NotebookPen', capability: ['canManageExaminations', 'canEnterExamResults'], roles: ['teacher'] },
      { title: 'Exams', href: '/academics/exams', icon: 'FileText', capability: ['canManageExaminations', 'canEnterExamResults'], roles: ['teacher'] },
      { title: 'My timetable', href: '/academics/my-timetable', icon: 'CalendarDays', roles: ['teacher'] },
    ],
  },
  {
    label: 'Messages',
    items: [
      { title: 'Inbox', href: '/communications', icon: 'Megaphone', capability: 'isStaff', roles: ['teacher'] },
    ],
  },
]

export const financeNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['finance'] },
      { title: 'Finance', href: '/finance', icon: 'Wallet', capability: 'canManageFinance', roles: ['finance'] },
      { title: 'Communications', href: '/communications', icon: 'Megaphone', capability: 'isStaff', roles: ['finance'] },
      { title: 'Audit trail', href: '/hr?tab=audit', icon: 'ScrollText', capability: 'canViewAuditLogs', roles: ['finance'] },
    ],
  },
  {
    label: 'Tools',
    items: [
      { title: 'Assistant', href: '/assistant', icon: 'Bot', capability: 'isStaff', roles: ['finance'] },
    ],
  },
]

export const accountsNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['accounts'] },
      { title: 'Finance', href: '/finance', icon: 'Wallet', capability: 'canManageFinance', roles: ['accounts'] },
      { title: 'Communications', href: '/communications', icon: 'Megaphone', capability: 'isStaff', roles: ['accounts'] },
      { title: 'Audit trail', href: '/hr?tab=audit', icon: 'ScrollText', capability: 'canViewAuditLogs', roles: ['accounts'] },
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
        capability: ['canManageExaminations', 'canEnterExamResults'],
        roles: ['examination_officer'],
      },
    ],
  },
  {
    label: 'Examinations',
    items: [
      { title: 'Exams', href: '/academics/exams', icon: 'FileText', capability: 'canManageExaminations', roles: ['examination_officer'] },
      { title: 'Exam schedule', href: '/academics/exam-schedules', icon: 'CalendarClock', capability: 'canManageExaminations', roles: ['examination_officer'] },
      { title: 'Gradebook', href: '/academics/grades', icon: 'BookOpen', capability: ['canManageExaminations', 'canEnterExamResults'], roles: ['examination_officer'] },
      { title: 'Class tests', href: '/academics/tests', icon: 'NotebookPen', capability: 'canManageExaminations', roles: ['examination_officer'] },
    ],
  },
  {
    label: 'Communications',
    items: [
      { title: 'Communications', href: '/communications', icon: 'Megaphone', capability: 'isStaff', roles: ['examination_officer'] },
      { title: 'Assistant', href: '/assistant', icon: 'Bot', capability: 'isStaff', roles: ['examination_officer'] },
    ],
  },
]

export const parentNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Home', href: '/portal', icon: 'Home', capability: 'isParent', roles: ['parent'] },
      { title: 'My profile', href: '/profile', icon: 'UserCog', capability: 'isParent', roles: ['parent'] },
    ],
  },
  {
    label: 'Family',
    items: [
      { title: 'My children', href: '/portal/children', icon: 'Users', capability: 'isParent', roles: ['parent'] },
      { title: 'School life', href: '/portal/hub', icon: 'Layers', capability: 'isParent', roles: ['parent'] },
    ],
  },
]

export const studentNavigation: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { title: 'Dashboard', href: '/student', icon: 'Home', roles: ['student'] },
      { title: 'My profile', href: '/profile', icon: 'UserCog', roles: ['student'] },
    ],
  },
  {
    label: 'My school',
    items: [
      { title: 'Timetable', href: '/student/timetable', icon: 'CalendarDays', roles: ['student'] },
      { title: 'Performance', href: '/student/performance', icon: 'BarChart3', roles: ['student'] },
      { title: 'Attendance', href: '/student/attendance', icon: 'ClipboardCheck', roles: ['student'] },
      { title: 'Exams', href: '/student/exams', icon: 'FileText', roles: ['student'] },
      { title: 'Assignments', href: '/student/assignments', icon: 'NotebookPen', roles: ['student'] },
      { title: 'Announcements', href: '/student/announcements', icon: 'Megaphone', roles: ['student'] },
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
