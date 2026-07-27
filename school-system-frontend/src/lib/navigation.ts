import type { NavGroup, NavItem } from '@/types/navigation'

/**
 * Admin/staff sidebar mirrors EduDash menu order and labels.
 * Parents with children are expanders; leaf links use existing hub redirects.
 */
export const staffNavigation: NavGroup[] = [
  {
    label: 'Menu',
    items: [
      {
        title: 'Dashboard',
        icon: 'LayoutDashboard',
        capability: 'isStaff',
        items: [
          { title: 'School', href: '/', capability: 'isStaff' },
          { title: 'Student', href: '/dashboard/student', capability: 'canManageTeachers' },
          { title: 'Teacher', href: '/dashboard/teacher', capability: 'canManageTeachers' },
          { title: 'Parent', href: '/dashboard/parent', capability: 'canManageTeachers' },
          { title: 'LMS', href: '/dashboard/lms', capability: 'isStaff' },
        ],
      },

      {
        title: 'Students',
        icon: 'GraduationCap',
        capability: 'canManageStudents',
        items: [
          { title: 'Add New', href: '/students?create=1', capability: 'canManageStudents' },
          { title: 'Student List', href: '/students', capability: 'canManageStudents' },
          { title: 'Suspended', href: '/students/suspended', capability: 'canManageStudents' },
          { title: 'Student Categories', href: '/people/categories', capability: 'canManageStudents' },
          { title: 'Enrollment', href: '/enrollment', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Teachers',
        icon: 'Users',
        capability: 'canManageTeachers',
        items: [
          { title: 'Add New', href: '/teachers?create=1', capability: 'canManageTeachers' },
          { title: 'Teacher List', href: '/teachers', capability: 'canManageTeachers' },
          { title: 'Teacher Timetable', href: '/academics/timetable', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Guardian',
        icon: 'UserCheck',
        capability: 'canManageTeachers',
        items: [
          { title: 'Add New', href: '/guardians?create=1', capability: 'canManageTeachers' },
          { title: 'Guardians List', href: '/guardians', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Classes',
        icon: 'School',
        capability: 'canManageTeachers',
        items: [
          { title: 'Class List', href: '/academics/classes', capability: 'canManageTeachers' },
          { title: 'Section', href: '/academics/streams', capability: 'canManageTeachers' },
          { title: 'Subjects', href: '/academics/subjects', capability: 'canManageTeachers' },
          { title: 'Class Room', href: '/academics/rooms', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Examinations',
        icon: 'FileText',
        capability: ['canManageExaminations', 'canEnterExamResults'],
        items: [
          { title: 'Exam', href: '/academics/exams', capability: ['canManageExaminations', 'canEnterExamResults'] },
          { title: 'Exam Schedule', href: '/academics/exam-schedules', capability: 'canManageExaminations' },
          { title: 'Exam Result', href: '/academics/grades', capability: ['canManageExaminations', 'canEnterExamResults'] },
        ],
      },
      {
        title: 'Fees Collection',
        icon: 'Wallet',
        capability: 'canManageFinance',
        items: [
          { title: 'Fees Collect', href: '/finance/payments', capability: 'canManageFinance' },
          { title: 'Fees Type', href: '/finance/fee-categories', capability: 'canManageFinance' },
          { title: 'Fees Group', href: '/finance/fee-groups', capability: 'canManageFinance' },
          { title: 'Fees Discount', href: '/finance/fee-discounts', capability: 'canManageFinance' },
        ],
      },
      {
        title: 'Attendance',
        icon: 'ClipboardCheck',
        capability: 'canManageStudents',
        items: [
          { title: 'Student Attendance', href: '/academics/attendance', capability: 'canManageStudents' },
          { title: 'Teacher Attendance', href: '/hr/staff-attendance?staff=teacher', capability: 'canManageTeachers' },
          { title: 'Employee Attendance', href: '/hr/staff-attendance?staff=employee', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Leaves',
        icon: 'Palmtree',
        capability: 'canManageTeachers',
        items: [
          { title: 'Leave Types', href: '/hr/leave-types', capability: 'canManageTeachers' },
          { title: 'Leave Request', href: '/hr/leave', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Certificate',
        href: '/academics/certificates',
        icon: 'Award',
        capability: 'canManageTeachers',
      },
      {
        title: 'Library',
        icon: 'Library',
        capability: 'canManageLibrary',
        items: [
          { title: 'Books', href: '/operations/library/books', capability: 'canManageLibrary' },
          { title: 'Members', href: '/operations/library/members', capability: 'canManageLibrary' },
          { title: 'Issue Return', href: '/operations/library/loans', capability: 'canManageLibrary' },
        ],
      },
      {
        title: 'Accounts',
        icon: 'BookOpen',
        capability: 'canManageFinance',
        items: [
          { title: 'Income Head', href: '/finance/income-heads', capability: 'canManageFinance' },
          { title: 'Income List', href: '/finance/income', capability: 'canManageFinance' },
          { title: 'Expense Head', href: '/finance/expense-heads', capability: 'canManageFinance' },
          { title: 'Expense List', href: '/finance/expense', capability: 'canManageFinance' },
          { title: 'Transaction', href: '/finance/transactions', capability: 'canManageFinance' },
          { title: 'Invoices', href: '/finance/invoices', capability: 'canManageFinance' },
        ],
      },
      {
        title: 'HRM',
        icon: 'IdCard',
        capability: 'canManageTeachers',
        items: [
          { title: 'Add New', href: '/hr/employees?create=1', capability: 'canManageTeachers' },
          { title: 'Employee List', href: '/hr/employees', capability: 'canManageTeachers' },
          { title: 'Payroll', href: '/finance/payroll', capability: 'canManageFinance' },
          { title: 'Designation', href: '/hr/designations', capability: 'canManageTeachers' },
          { title: 'Department', href: '/academics/departments', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Notice Board',
        href: '/communications/announcements',
        icon: 'Megaphone',
        capability: 'isStaff',
      },
      {
        title: 'Event',
        href: '/operations/events',
        icon: 'CalendarHeart',
        capability: 'canManageTeachers',
      },
      {
        title: 'Message',
        href: '/communications/threads',
        icon: 'MessageSquare',
        capability: 'isStaff',
      },
      {
        title: 'Transport',
        href: '/operations/transport',
        icon: 'Bus',
        capability: 'canManageTransport',
      },
      {
        title: 'Hostel',
        href: '/operations/hostels',
        icon: 'Building2',
        capability: 'canManageTeachers',
      },
      {
        title: 'Inventory',
        href: '/operations/inventory',
        icon: 'Package',
        capability: 'canManageInventory',
      },
      {
        title: 'Subscription Plan',
        href: '/admin/subscription',
        icon: 'CreditCard',
        capability: 'canManageTeachers',
      },
      {
        title: 'Role & Access',
        href: '/admin/roles',
        icon: 'UserCog',
        capability: 'canManageTeachers',
      },
      {
        title: 'Assign Role',
        href: '/admin/users',
        icon: 'UserCog',
        capability: 'canManageTeachers',
      },
      {
        title: 'Settings',
        icon: 'Settings',
        capability: 'canManageTeachers',
        items: [
          { title: 'General', href: '/settings', capability: 'canManageTeachers' },
          { title: 'Notification', href: '/settings?tab=notifications', capability: 'canManageTeachers' },
          { title: 'Currencies', href: '/settings?tab=currencies', capability: 'canManageTeachers' },
          { title: 'Languages', href: '/settings?tab=languages', capability: 'canManageTeachers' },
          { title: 'Terms', href: '/academics/terms', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Reports',
        href: '/reports',
        icon: 'FileBarChart',
        capability: 'canManageTeachers',
      },
      {
        title: 'Workflows',
        href: '/workflows',
        icon: 'GitBranch',
        capability: 'canManageTeachers',
        badge: 'workflows',
      },
    ],
  },
]

function withAdminRoles(items: NavItem[]): NavItem[] {
  return items.map((item) => ({
    ...item,
    roles: ['admin'],
    items: item.items ? withAdminRoles(item.items) : undefined,
  }))
}

export const adminNavigation: NavGroup[] = staffNavigation.map((group) => ({
  ...group,
  items: withAdminRoles(group.items),
}))

export const teacherNavigation: NavGroup[] = [
  {
    label: 'Menu',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['teacher'] },
      { title: 'Teaching', href: '/teaching', icon: 'BookOpen', capability: 'isStaff', roles: ['teacher'] },
      {
        title: 'Attendance',
        icon: 'ClipboardCheck',
        capability: 'canManageStudents',
        roles: ['teacher'],
        items: [
          { title: 'Student Attendance', href: '/academics/attendance', capability: 'canManageStudents', roles: ['teacher'] },
        ],
      },
      {
        title: 'Examinations',
        icon: 'FileText',
        capability: ['canManageExaminations', 'canEnterExamResults'],
        roles: ['teacher'],
        items: [
          { title: 'Exam', href: '/academics/exams', capability: ['canManageExaminations', 'canEnterExamResults'], roles: ['teacher'] },
          { title: 'Exam Result', href: '/academics/grades', capability: ['canManageExaminations', 'canEnterExamResults'], roles: ['teacher'] },
        ],
      },
      { title: 'Teacher Timetable', href: '/academics/my-timetable', icon: 'CalendarDays', roles: ['teacher'] },
      { title: 'Notice Board', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['teacher'] },
      { title: 'Message', href: '/communications/threads', icon: 'MessageSquare', capability: 'isStaff', roles: ['teacher'] },
      { title: 'My profile', href: '/profile', icon: 'UserCog', capability: 'isStaff', roles: ['teacher'] },
    ],
  },
]

export const financeNavigation: NavGroup[] = [
  {
    label: 'Menu',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['finance'] },
      {
        title: 'Fees Collection',
        icon: 'Wallet',
        capability: 'canManageFinance',
        roles: ['finance'],
        items: [
          { title: 'Fees Collect', href: '/finance/payments', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Fees Type', href: '/finance/fee-categories', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Fees Group', href: '/finance/fee-groups', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Fees Discount', href: '/finance/fee-discounts', capability: 'canManageFinance', roles: ['finance'] },
        ],
      },
      {
        title: 'Accounts',
        icon: 'BookOpen',
        capability: 'canManageFinance',
        roles: ['finance'],
        items: [
          { title: 'Income Head', href: '/finance/income-heads', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Income List', href: '/finance/income', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Expense Head', href: '/finance/expense-heads', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Expense List', href: '/finance/expense', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Transaction', href: '/finance/transactions', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Invoices', href: '/finance/invoices', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Payroll', href: '/finance/payroll', capability: 'canManageFinance', roles: ['finance'] },
        ],
      },
      { title: 'Notice Board', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['finance'] },
      { title: 'Audit trail', href: '/compliance/audit', icon: 'ScrollText', capability: 'canViewAuditLogs', roles: ['finance'] },
    ],
  },
]

export const accountsNavigation: NavGroup[] = [
  {
    label: 'Menu',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['accounts'] },
      {
        title: 'Fees Collection',
        icon: 'Wallet',
        capability: 'canManageFinance',
        roles: ['accounts'],
        items: [
          { title: 'Fees Collect', href: '/finance/payments', capability: 'canManageFinance', roles: ['accounts'] },
          { title: 'Invoices', href: '/finance/invoices', capability: 'canManageFinance', roles: ['accounts'] },
        ],
      },
      {
        title: 'Accounts',
        icon: 'BookOpen',
        capability: 'canManageFinance',
        roles: ['accounts'],
        items: [
          { title: 'Transaction', href: '/finance/transactions', capability: 'canManageFinance', roles: ['accounts'] },
          { title: 'Income Head', href: '/finance/income-heads', capability: 'canManageFinance', roles: ['accounts'] },
          { title: 'Income List', href: '/finance/income', capability: 'canManageFinance', roles: ['accounts'] },
          { title: 'Expense Head', href: '/finance/expense-heads', capability: 'canManageFinance', roles: ['accounts'] },
          { title: 'Expense List', href: '/finance/expense', capability: 'canManageFinance', roles: ['accounts'] },
        ],
      },
      { title: 'Notice Board', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['accounts'] },
    ],
  },
]

export const examinationOfficerNavigation: NavGroup[] = [
  {
    label: 'Menu',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['examination_officer'] },
      {
        title: 'Examinations',
        icon: 'FileText',
        capability: 'canManageExaminations',
        roles: ['examination_officer'],
        items: [
          { title: 'Exam', href: '/academics/exams', capability: 'canManageExaminations', roles: ['examination_officer'] },
          { title: 'Exam Schedule', href: '/academics/exam-schedules', capability: 'canManageExaminations', roles: ['examination_officer'] },
          { title: 'Exam Result', href: '/academics/grades', capability: ['canManageExaminations', 'canEnterExamResults'], roles: ['examination_officer'] },
        ],
      },
      { title: 'Notice Board', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['examination_officer'] },
    ],
  },
]

export const parentNavigation: NavGroup[] = [
  {
    label: 'Menu',
    items: [
      { title: 'Dashboard', href: '/portal', icon: 'Home', capability: 'isParent', roles: ['parent'] },
      { title: 'My children', href: '/portal/children', icon: 'Users', capability: 'isParent', roles: ['parent'] },
      { title: 'School life', href: '/portal/hub', icon: 'Layers', capability: 'isParent', roles: ['parent'] },
      { title: 'My profile', href: '/profile', icon: 'UserCog', capability: 'isParent', roles: ['parent'] },
    ],
  },
]

export const studentNavigation: NavGroup[] = [
  {
    label: 'Menu',
    items: [
      { title: 'Dashboard', href: '/student', icon: 'Home', roles: ['student'] },
      { title: 'Timetable', href: '/student/timetable', icon: 'CalendarDays', roles: ['student'] },
      { title: 'Exam Result', href: '/student/performance', icon: 'BarChart3', roles: ['student'] },
      { title: 'Attendance', href: '/student/attendance', icon: 'ClipboardCheck', roles: ['student'] },
      { title: 'Examinations', href: '/student/exams', icon: 'FileText', roles: ['student'] },
      { title: 'Assignments', href: '/student/assignments', icon: 'NotebookPen', roles: ['student'] },
      { title: 'Notice Board', href: '/student/announcements', icon: 'Megaphone', roles: ['student'] },
      { title: 'Fees Collection', href: '/student/fees', icon: 'Wallet', roles: ['student'] },
      { title: 'My profile', href: '/profile', icon: 'UserCog', roles: ['student'] },
    ],
  },
]

export const platformNavigation: NavGroup[] = [
  {
    label: 'Menu',
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
