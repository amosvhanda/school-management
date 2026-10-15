import type { NavGroup, NavItem } from '@/types/navigation'

/**
 * Staff sidebar mirrors ProSkool section order and expandable modules.
 * Child hrefs map to existing app routes/hubs only (no dead template pages).
 */
export const staffNavigation: NavGroup[] = [
  {
    label: '',
    items: [
      {
        title: 'Dashboard',
        href: '/',
        icon: 'LayoutDashboard',
        capability: 'isStaff',
      },
      {
        title: 'Application',
        href: '/enrollment',
        icon: 'ClipboardList',
        capability: 'canManageTeachers',
      },
      {
        title: 'Reports',
        href: '/reports',
        icon: 'FileBarChart',
        capability: 'canManageTeachers',
      },
    ],
  },
  {
    label: 'People & Groups',
    items: [
      {
        title: 'Students',
        icon: 'GraduationCap',
        capability: 'canManageStudents',
        items: [
          { title: 'All Students', href: '/people?tab=students', capability: 'canManageStudents' },
          { title: 'Add Student', href: '/students/create', capability: 'canManageStudents' },
          { title: 'Student Profile', href: '/students', capability: 'canManageStudents' },
          // Admissions is MAIN → Application (/enrollment) — avoid a second active link.
          { title: 'Student Promotion', href: '/students/promotion', capability: 'canManageStudents' },
          { title: 'Alumni', href: '/enterprise/alumni', capability: 'canManageTeachers' },
          { title: 'Student Attendance History', href: '/students/attendance-history', capability: 'canManageStudents' },
          { title: 'Student Documents', href: '/students/documents', capability: 'canManageStudents' },
        ],
      },
      {
        title: 'Teachers',
        icon: 'Users',
        capability: 'canManageTeachers',
        items: [
          { title: 'Teachers', href: '/teachers', capability: 'canManageTeachers' },
          { title: 'Add Teacher', href: '/teachers?create=1', capability: 'canManageTeachers' },
          { title: 'Teacher Profile', href: '/people?tab=teachers', capability: 'canManageTeachers' },
          { title: 'Teacher Timetable', href: '/academics/timetable', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Parents',
        icon: 'UsersRound',
        capability: 'canManageTeachers',
        items: [
          { title: 'Parents', href: '/guardians', capability: 'canManageTeachers' },
          { title: 'Add Parent', href: '/people?tab=guardians&create=1', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Guardians',
        icon: 'Heart',
        capability: 'canManageTeachers',
        items: [
          { title: 'Guardians', href: '/people?tab=guardians', capability: 'canManageTeachers' },
          { title: 'Add Guardian', href: '/people?tab=guardians&create=1', capability: 'canManageTeachers' },
          { title: 'Consent', href: '/hr?tab=consent', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Staff',
        icon: 'UserCog',
        capability: 'canManageTeachers',
        items: [
          { title: 'Staff List', href: '/hr/employees', capability: 'canManageTeachers' },
          { title: 'Add Staff', href: '/hr?tab=employees&create=1', capability: 'canManageTeachers' },
          { title: 'Designations', href: '/hr/designations', capability: 'canManageTeachers' },
          { title: 'Recruitment', href: '/hr/recruitment', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Users',
        icon: 'User',
        capability: 'canManageTeachers',
        items: [
          { title: 'Users', href: '/admin/users', capability: 'canManageTeachers' },
          { title: 'Roles & Access', href: '/admin/roles', capability: 'canManageTeachers' },
        ],
      },
    ],
  },
  {
    label: 'Academic',
    items: [
      {
        title: 'Classes & Sections',
        icon: 'School',
        capability: 'canManageTeachers',
        items: [
          { title: 'Classes', href: '/academics/classes', capability: 'canManageTeachers' },
          { title: 'Sections', href: '/academics/streams', capability: 'canManageTeachers' },
          { title: 'Rooms', href: '/academics/rooms', capability: 'canManageTeachers' },
          { title: 'Grade Levels', href: '/settings?tab=grade-levels', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Subjects & Syllabus',
        icon: 'BookOpen',
        capability: 'canManageTeachers',
        items: [
          { title: 'Subjects', href: '/academics/subjects', capability: 'canManageTeachers' },
          { title: 'Departments', href: '/academics/departments', capability: 'canManageTeachers' },
          { title: 'Subject Packages', href: '/settings?tab=subject-packages', capability: 'canManageTeachers' },
          { title: 'Grading Scales', href: '/settings?tab=grading', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Timetable',
        icon: 'CalendarDays',
        capability: 'canManageTeachers',
        items: [
          { title: 'Class Timetable', href: '/academics/timetable', capability: 'canManageTeachers' },
          { title: 'My Timetable', href: '/academics/my-timetable', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Learning',
        icon: 'BookMarked',
        capability: 'isStaff',
        items: [
          { title: 'Teaching Hub', href: '/teaching', capability: 'isStaff' },
          { title: 'LMS', href: '/dashboard/lms', capability: 'isStaff' },
          { title: 'Holiday Programs', href: '/academics/holiday-programs', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Examination',
        icon: 'FileText',
        capability: ['canManageExaminations', 'canEnterExamResults'],
        items: [
          { title: 'Exams', href: '/academics/exams', capability: ['canManageExaminations', 'canEnterExamResults'] },
          { title: 'Exam Schedule', href: '/academics/exam-schedules', capability: 'canManageExaminations' },
          { title: 'Exam Results', href: '/academics/grades', capability: ['canManageExaminations', 'canEnterExamResults'] },
        ],
      },
    ],
  },
  {
    label: 'HRM',
    items: [
      {
        title: 'HR & Payroll',
        icon: 'Briefcase',
        capability: 'canManageTeachers',
        items: [
          { title: 'Employee Payroll', href: '/finance/payroll', capability: 'canManageFinance' },
          { title: 'Employees', href: '/hr/employees', capability: 'canManageTeachers' },
          { title: 'Leave Policies', href: '/hr/leave-types', capability: 'canManageTeachers' },
          { title: 'Leave Requests', href: '/hr/leave', capability: 'canManageTeachers' },
          { title: 'Staff Tasks', href: '/hr/staff-tasks', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Holidays',
        icon: 'Palmtree',
        capability: 'canManageTeachers',
        items: [
          { title: 'Holiday Programs', href: '/academics/holiday-programs', capability: 'canManageTeachers' },
          { title: 'Academic Terms', href: '/academics/terms', capability: 'canManageTeachers' },
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
    ],
  },
  {
    label: 'Finance & Accounts',
    items: [
      {
        title: 'Fees',
        icon: 'Wallet',
        capability: 'canManageFinance',
        items: [
          { title: 'Fee Structures', href: '/finance/fee-structures', capability: 'canManageFinance' },
          { title: 'Fee Categories', href: '/finance/fee-categories', capability: 'canManageFinance' },
          { title: 'Fee Groups', href: '/finance/fee-groups', capability: 'canManageFinance' },
          { title: 'Fee Discounts', href: '/finance/fee-discounts', capability: 'canManageFinance' },
        ],
      },
      {
        title: 'Billing',
        icon: 'Receipt',
        capability: 'canManageFinance',
        items: [
          { title: 'Collect Fees', href: '/finance/payments', capability: 'canManageFinance' },
          { title: 'Invoices', href: '/finance/invoices', capability: 'canManageFinance' },
          { title: 'Aging', href: '/finance/reports', capability: 'canManageFinance' },
        ],
      },
      {
        title: 'Accounts',
        icon: 'BookOpen',
        capability: 'canManageFinance',
        items: [
          { title: 'Income', href: '/finance/income', capability: 'canManageFinance' },
          { title: 'Expense', href: '/finance/expense', capability: 'canManageFinance' },
          { title: 'Transactions', href: '/finance/transactions', capability: 'canManageFinance' },
          { title: 'Cash Flow', href: '/finance/cash-flow', capability: 'canManageFinance' },
          { title: 'Income Heads', href: '/finance/income-heads', capability: 'canManageFinance' },
          { title: 'Expense Heads', href: '/finance/expense-heads', capability: 'canManageFinance' },
        ],
      },
    ],
  },
  {
    label: 'Campus Infrastructure',
    items: [
      {
        title: 'Library',
        icon: 'Library',
        capability: 'canManageLibrary',
        items: [
          { title: 'Books', href: '/operations/library/books', capability: 'canManageLibrary' },
          { title: 'Members', href: '/operations/library/members', capability: 'canManageLibrary' },
          { title: 'Issue / Return', href: '/operations/library/loans', capability: 'canManageLibrary' },
        ],
      },
      {
        title: 'Transport',
        icon: 'Bus',
        capability: 'canManageTransport',
        items: [
          { title: 'Transport', href: '/operations/transport', capability: 'canManageTransport' },
        ],
      },
      {
        title: 'Hostel',
        icon: 'Building2',
        capability: 'canManageHostel',
        items: [
          { title: 'Hostels', href: '/operations/hostels', capability: 'canManageHostel' },
        ],
      },
      {
        title: 'Assets',
        icon: 'Package',
        capability: 'canManageInventory',
        items: [
          { title: 'Inventory', href: '/operations/inventory', capability: 'canManageInventory' },
          { title: 'Assets', href: '/finance/assets', capability: 'canManageFinance' },
          { title: 'Procurement', href: '/finance/procurement', capability: 'canManageFinance' },
        ],
      },
      {
        title: 'Security',
        icon: 'Shield',
        capability: 'canManageReception',
        items: [
          { title: 'Front Office', href: '/operations/front-office', capability: 'canManageReception' },
          { title: 'Discipline', href: '/hr/discipline', capability: 'canManageStudents' },
        ],
      },
      {
        title: 'Medical',
        icon: 'HeartPulse',
        capability: 'canManageHealth',
        items: [
          { title: 'Clinic', href: '/operations/health', capability: 'canManageHealth' },
        ],
      },
    ],
  },
  {
    label: 'Communication',
    items: [
      {
        title: 'Broadcast',
        icon: 'Megaphone',
        capability: 'isStaff',
        items: [
          { title: 'Announcements', href: '/communications/announcements', capability: 'isStaff' },
          { title: 'Add Announcement', href: '/communications/announcements?create=1', capability: 'isStaff' },
        ],
      },
      {
        title: 'Messages',
        icon: 'MessageSquare',
        capability: 'isStaff',
        items: [
          { title: 'Threads', href: '/communications/threads', capability: 'isStaff' },
        ],
      },
      {
        title: 'Notifications',
        icon: 'Bell',
        capability: 'canManageTeachers',
        items: [
          { title: 'Notification Settings', href: '/settings?tab=notifications', capability: 'canManageTeachers' },
          { title: 'Workflows', href: '/workflows', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Behavior',
        icon: 'ShieldAlert',
        capability: 'canManageStudents',
        items: [
          { title: 'Discipline', href: '/hr/discipline', capability: 'canManageStudents' },
          { title: 'Policies', href: '/hr?tab=policies', capability: 'canManageTeachers' },
        ],
      },
    ],
  },
  {
    label: 'Insights',
    items: [
      {
        title: 'Reports & Analytics',
        icon: 'ChartNoAxesCombined',
        capability: 'canManageTeachers',
        items: [
          { title: 'Reports', href: '/reports', capability: 'canManageTeachers' },
          { title: 'Analytics', href: '/analytics', capability: 'canManageTeachers' },
          { title: 'People Analytics', href: '/people/analytics', capability: 'canManageStudents' },
          { title: 'Academic Analytics', href: '/academics/analytics', capability: 'canManageTeachers' },
          { title: 'Finance Analytics', href: '/finance/analytics', capability: 'canManageFinance' },
        ],
      },
      {
        title: 'AI Insights',
        icon: 'Sparkles',
        capability: 'isStaff',
        items: [
          { title: 'AI Assistant', href: '/assistant', capability: 'isStaff' },
          { title: 'Command Center', href: '/enterprise', capability: 'canManageTeachers' },
          { title: 'Student Intelligence', href: '/enterprise/academic', capability: 'canManageTeachers' },
          { title: 'Fee Forecast', href: '/enterprise/finance', capability: 'canManageFinance' },
          { title: 'Teacher Productivity', href: '/enterprise/hr', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Achievements',
        icon: 'Trophy',
        capability: 'canManageTeachers',
        items: [
          { title: 'Campaigns', href: '/enterprise/campaigns', capability: 'canManageTeachers' },
          { title: 'Alumni', href: '/enterprise/alumni', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Certificates',
        icon: 'Award',
        capability: 'canManageTeachers',
        items: [
          { title: 'Certificates', href: '/academics/certificates', capability: 'canManageTeachers' },
        ],
      },
    ],
  },
  {
    label: 'Marketing & Apps',
    items: [
      {
        title: 'Events',
        icon: 'CalendarHeart',
        capability: 'canManageTeachers',
        items: [
          { title: 'Events', href: '/operations/events', capability: 'canManageTeachers' },
          { title: 'School Trips', href: '/operations/school-trips', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Multi School',
        icon: 'Building',
        capability: 'canManageTeachers',
        items: [
          { title: 'Branch Management', href: '/admin/branch-management', capability: 'canManageTeachers' },
          { title: 'Platform', href: '/platform', capability: 'isSuperAdmin' },
          { title: 'Licenses', href: '/platform/licenses', capability: 'isSuperAdmin' },
        ],
      },
      {
        title: 'Subscription',
        icon: 'CreditCard',
        capability: 'canManageTeachers',
        items: [
          { title: 'Subscription Plan', href: '/admin/subscription', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Mobile App',
        icon: 'Smartphone',
        capability: 'canManageTeachers',
        items: [
          { title: 'Push Notifications', href: '/settings?tab=notifications', capability: 'canManageTeachers' },
        ],
      },
    ],
  },
  {
    label: 'Settings',
    items: [
      {
        title: 'General',
        icon: 'Settings',
        capability: 'canManageTeachers',
        items: [
          { title: 'School Profile', href: '/settings', capability: 'canManageTeachers' },
          { title: 'Custom Fields', href: '/settings?tab=custom-fields', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Account & Security',
        icon: 'ShieldCheck',
        capability: 'isStaff',
        items: [
          { title: 'My Profile', href: '/profile', capability: 'isStaff' },
          { title: 'Audit Trail', href: '/compliance/audit', capability: 'canViewAuditLogs' },
        ],
      },
      {
        title: 'Academic',
        icon: 'School',
        capability: 'canManageTeachers',
        items: [
          { title: 'Terms', href: '/academics/terms', capability: 'canManageTeachers' },
          { title: 'Classes', href: '/settings?tab=classes', capability: 'canManageTeachers' },
          { title: 'Subjects', href: '/settings?tab=subjects', capability: 'canManageTeachers' },
          { title: 'Houses', href: '/settings?tab=houses', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Finance Settings',
        icon: 'CircleDollarSign',
        capability: 'canManageFinance',
        items: [
          { title: 'Currencies', href: '/settings?tab=currencies', capability: 'canManageTeachers' },
          { title: 'Fee Structures', href: '/finance/fee-structures', capability: 'canManageFinance' },
        ],
      },
      {
        title: 'Communication',
        icon: 'Mail',
        capability: 'canManageTeachers',
        items: [
          { title: 'Notifications', href: '/settings?tab=notifications', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'Localization',
        icon: 'Globe',
        capability: 'canManageTeachers',
        items: [
          { title: 'Languages', href: '/settings?tab=languages', capability: 'canManageTeachers' },
          { title: 'Currencies', href: '/settings?tab=currencies', capability: 'canManageTeachers' },
        ],
      },
      {
        title: 'System',
        icon: 'Server',
        capability: 'canManageTeachers',
        items: [
          { title: 'Workflows', href: '/workflows', capability: 'canManageTeachers' },
          { title: 'Subscription', href: '/admin/subscription', capability: 'canManageTeachers' },
        ],
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
    label: '',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['teacher'] },
      { title: 'Teaching', href: '/teaching', icon: 'BookOpen', capability: 'isStaff', roles: ['teacher'] },
      { title: 'My tasks', href: '/teaching?tab=my-tasks', icon: 'ListTodo', capability: 'isStaff', roles: ['teacher'] },
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
        title: 'Examination',
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
      { title: 'Messages', href: '/communications/threads', icon: 'MessageSquare', capability: 'isStaff', roles: ['teacher'] },
      { title: 'My profile', href: '/profile', icon: 'UserCog', capability: 'isStaff', roles: ['teacher'] },
    ],
  },
]

export const financeNavigation: NavGroup[] = [
  {
    label: '',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['finance'] },
      {
        title: 'Fees',
        icon: 'Wallet',
        capability: 'canManageFinance',
        roles: ['finance'],
        items: [
          { title: 'Collect Fees', href: '/finance/payments', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Fee Structures', href: '/finance/fee-structures', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Fee Categories', href: '/finance/fee-categories', capability: 'canManageFinance', roles: ['finance'] },
        ],
      },
      {
        title: 'Accounts',
        icon: 'BookOpen',
        capability: 'canManageFinance',
        roles: ['finance'],
        items: [
          { title: 'Invoices', href: '/finance/invoices', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Transactions', href: '/finance/transactions', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Payroll', href: '/finance/payroll', capability: 'canManageFinance', roles: ['finance'] },
          { title: 'Cash Flow', href: '/finance/cash-flow', capability: 'canManageFinance', roles: ['finance'] },
        ],
      },
      { title: 'Notice Board', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['finance'] },
      { title: 'Audit trail', href: '/compliance/audit', icon: 'ScrollText', capability: 'canViewAuditLogs', roles: ['finance'] },
    ],
  },
]

export const accountsNavigation: NavGroup[] = [
  {
    label: '',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['accounts'] },
      {
        title: 'Billing',
        icon: 'Wallet',
        capability: 'canManageFinance',
        roles: ['accounts'],
        items: [
          { title: 'Collect Fees', href: '/finance/payments', capability: 'canManageFinance', roles: ['accounts'] },
          { title: 'Invoices', href: '/finance/invoices', capability: 'canManageFinance', roles: ['accounts'] },
        ],
      },
      {
        title: 'Accounts',
        icon: 'BookOpen',
        capability: 'canManageFinance',
        roles: ['accounts'],
        items: [
          { title: 'Transactions', href: '/finance/transactions', capability: 'canManageFinance', roles: ['accounts'] },
          { title: 'Income', href: '/finance/income', capability: 'canManageFinance', roles: ['accounts'] },
          { title: 'Expense', href: '/finance/expense', capability: 'canManageFinance', roles: ['accounts'] },
        ],
      },
      { title: 'Notice Board', href: '/communications/announcements', icon: 'Megaphone', capability: 'isStaff', roles: ['accounts'] },
    ],
  },
]

export const examinationOfficerNavigation: NavGroup[] = [
  {
    label: '',
    items: [
      { title: 'Dashboard', href: '/', icon: 'LayoutDashboard', roles: ['examination_officer'] },
      {
        title: 'Examination',
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
    label: '',
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
    label: '',
    items: [
      { title: 'Dashboard', href: '/student', icon: 'Home', roles: ['student'] },
      { title: 'Timetable', href: '/student/timetable', icon: 'CalendarDays', roles: ['student'] },
      { title: 'Exam Result', href: '/student/performance', icon: 'BarChart3', roles: ['student'] },
      { title: 'Attendance', href: '/student/attendance', icon: 'ClipboardCheck', roles: ['student'] },
      { title: 'Examinations', href: '/student/exams', icon: 'FileText', roles: ['student'] },
      { title: 'Assignments', href: '/student/assignments', icon: 'NotebookPen', roles: ['student'] },
      { title: 'Notice Board', href: '/student/announcements', icon: 'Megaphone', roles: ['student'] },
      { title: 'Fees', href: '/student/fees', icon: 'Wallet', roles: ['student'] },
      { title: 'My profile', href: '/profile', icon: 'UserCog', roles: ['student'] },
    ],
  },
]

export const platformNavigation: NavGroup[] = [
  {
    label: '',
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
