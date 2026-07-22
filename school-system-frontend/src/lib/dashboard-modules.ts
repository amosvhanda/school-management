import type { NavCapability } from '@/types/navigation'

export interface DashboardModule {
  title: string
  description: string
  href: string
  icon: string
  capability?: NavCapability | NavCapability[]
}

export interface DashboardModuleGroup {
  label: string
  modules: DashboardModule[]
}

export const TEACHER_DASHBOARD_MODULE_GROUPS: DashboardModuleGroup[] = [
  {
    label: 'Teaching',
    modules: [
      {
        title: 'My timetable',
        description: 'Lessons you teach this week',
        href: '/academics/my-timetable',
        icon: 'CalendarDays',
      },
      {
        title: 'Attendance register',
        description: 'Mark and review class attendance',
        href: '/academics/attendance',
        icon: 'ClipboardCheck',
        capability: 'canManageStudents',
      },
      {
        title: 'Exams',
        description: 'Enter marks and review exam outcomes',
        href: '/academics/exams',
        icon: 'FileText',
        capability: ['canManageExaminations', 'canEnterExamResults'],
      },
      {
        title: 'Academics analytics',
        description: 'Track attendance and performance trends',
        href: '/academics/analytics',
        icon: 'BarChart3',
        capability: ['canManageTeachers', 'canManageStudents', 'canEnterExamResults'],
      },
    ],
  },
  {
    label: 'Learners',
    modules: [
      {
        title: 'Students',
        description: 'View and update learner records',
        href: '/students',
        icon: 'GraduationCap',
        capability: 'canManageStudents',
      },
      {
        title: 'Guardians',
        description: 'Review parent and guardian contacts',
        href: '/guardians',
        icon: 'UserCheck',
        capability: 'canManageStudents',
      },
    ],
  },
  {
    label: 'Communication',
    modules: [
      {
        title: 'Messages',
        description: 'Reply to parent and staff threads',
        href: '/communications/threads',
        icon: 'MessageSquare',
        capability: 'isStaff',
      },
      {
        title: 'Announcements',
        description: 'Post updates to school audiences',
        href: '/communications/announcements',
        icon: 'Megaphone',
        capability: 'isStaff',
      },
      {
        title: 'Communications analytics',
        description: 'Monitor message and engagement activity',
        href: '/communications/analytics',
        icon: 'BarChart3',
        capability: 'isStaff',
      },
    ],
  },
  {
    label: 'Tools',
    modules: [
      {
        title: 'Assistant',
        description: 'Use the AI assistant for teaching tasks',
        href: '/assistant',
        icon: 'Bot',
        capability: 'isStaff',
      },
    ],
  },
]

export const ACCOUNTS_DASHBOARD_MODULE_GROUPS: DashboardModuleGroup[] = [
  {
    label: 'Finance operations',
    modules: [
      {
        title: 'Finance overview',
        description: 'Daily cash position and receivables snapshot',
        href: '/finance',
        icon: 'Wallet',
        capability: 'canManageFinance',
      },
      {
        title: 'Payments',
        description: 'Capture and reconcile collections',
        href: '/finance/payments',
        icon: 'CreditCard',
        capability: 'canManageFinance',
      },
      {
        title: 'Invoices',
        description: 'Issue invoices and monitor balances',
        href: '/finance/invoices',
        icon: 'Receipt',
        capability: 'canManageFinance',
      },
      {
        title: 'Transactions',
        description: 'Review ledgers and posting activity',
        href: '/finance/transactions',
        icon: 'ArrowLeftRight',
        capability: 'canManageFinance',
      },
      {
        title: 'Aging report',
        description: 'Track overdue balances by age buckets',
        href: '/finance/reports',
        icon: 'LineChart',
        capability: 'canManageFinance',
      },
      {
        title: 'Finance analytics',
        description: 'Collections and revenue performance insights',
        href: '/finance/analytics',
        icon: 'BarChart3',
        capability: 'canManageFinance',
      },
    ],
  },
  {
    label: 'Back office',
    modules: [
      {
        title: 'Payroll',
        description: 'Manage staff payroll disbursements',
        href: '/finance/payroll',
        icon: 'Banknote',
        capability: 'canManageFinance',
      },
      {
        title: 'Procurement',
        description: 'Handle purchase requests and approvals',
        href: '/operations/procurement',
        icon: 'ShoppingCart',
        capability: 'canManageFinance',
      },
      {
        title: 'Vendors',
        description: 'Maintain supplier records and contacts',
        href: '/operations/procurement/vendors',
        icon: 'Truck',
        capability: 'canManageFinance',
      },
      {
        title: 'Assets',
        description: 'Track financial assets and registers',
        href: '/operations/assets',
        icon: 'HardDrive',
        capability: 'canManageFinance',
      },
      {
        title: 'Instalment plans',
        description: 'Manage fee payment plan schedules',
        href: '/enterprise/finance/instalments',
        icon: 'CalendarClock',
        capability: 'canManageFinance',
      },
    ],
  },
  {
    label: 'Coordination',
    modules: [
      {
        title: 'Messages',
        description: 'Coordinate with parents and staff',
        href: '/communications/threads',
        icon: 'MessageSquare',
        capability: 'isStaff',
      },
      {
        title: 'Announcements',
        description: 'Post finance and billing updates',
        href: '/communications/announcements',
        icon: 'Megaphone',
        capability: 'isStaff',
      },
      {
        title: 'Audit trail',
        description: 'Review system activity and finance changes',
        href: '/compliance/audit',
        icon: 'ScrollText',
        capability: 'canViewAuditLogs',
      },
      {
        title: 'Assistant',
        description: 'Use AI support for operational tasks',
        href: '/assistant',
        icon: 'Bot',
        capability: 'isStaff',
      },
    ],
  },
]

export const FINANCE_DASHBOARD_MODULE_GROUPS: DashboardModuleGroup[] = [
  {
    label: 'Finance',
    modules: [
      {
        title: 'Finance overview',
        description: 'Daily collections and receivables snapshot',
        href: '/finance',
        icon: 'Wallet',
        capability: 'canManageFinance',
      },
      {
        title: 'Finance analytics',
        description: 'Revenue and collection trends',
        href: '/finance/analytics',
        icon: 'BarChart3',
        capability: 'canManageFinance',
      },
      {
        title: 'Payments',
        description: 'Record and reconcile fee payments',
        href: '/finance/payments',
        icon: 'CreditCard',
        capability: 'canManageFinance',
      },
      {
        title: 'Invoices',
        description: 'Bill students and track balances',
        href: '/finance/invoices',
        icon: 'Receipt',
        capability: 'canManageFinance',
      },
      {
        title: 'Fee structures',
        description: 'Configure fees by class and term',
        href: '/finance/fees',
        icon: 'Tags',
        capability: 'canManageFinance',
      },
      {
        title: 'Aging report',
        description: 'Outstanding balances by due age',
        href: '/finance/reports',
        icon: 'LineChart',
        capability: 'canManageFinance',
      },
    ],
  },
  {
    label: 'Accounting',
    modules: [
      {
        title: 'Transactions',
        description: 'Ledger and postings activity',
        href: '/finance/transactions',
        icon: 'ArrowLeftRight',
        capability: 'canManageFinance',
      },
      {
        title: 'Payroll',
        description: 'Salary processing and pending payouts',
        href: '/finance/payroll',
        icon: 'Banknote',
        capability: 'canManageFinance',
      },
      {
        title: 'Procurement',
        description: 'Purchase requests and approvals',
        href: '/operations/procurement',
        icon: 'ShoppingCart',
        capability: 'canManageFinance',
      },
      {
        title: 'Vendors',
        description: 'Supplier records and contacts',
        href: '/operations/procurement/vendors',
        icon: 'Truck',
        capability: 'canManageFinance',
      },
      {
        title: 'Assets',
        description: 'Financial asset register',
        href: '/operations/assets',
        icon: 'HardDrive',
        capability: 'canManageFinance',
      },
      {
        title: 'Enterprise finance',
        description: 'Advanced accounting modules',
        href: '/enterprise/finance',
        icon: 'Landmark',
        capability: 'canManageFinance',
      },
      {
        title: 'Instalment plans',
        description: 'Fee plan schedules and tracking',
        href: '/enterprise/finance/instalments',
        icon: 'CalendarClock',
        capability: 'canManageFinance',
      },
    ],
  },
]

/** All staff modules — single source for dashboard module grid and section hubs. */
export const STAFF_DASHBOARD_MODULE_GROUPS: DashboardModuleGroup[] = [
  {
    label: 'Overview',
    modules: [
      {
        title: 'School analytics',
        description: 'Cross-school performance dashboard',
        href: '/analytics',
        icon: 'BarChart3',
        capability: 'canManageTeachers',
      },
      {
        title: 'Workflows',
        description: 'Pending approvals and tasks',
        href: '/workflows',
        icon: 'GitBranch',
        capability: 'canManageTeachers',
      },
      {
        title: 'Workflow history',
        description: 'Completed approval records',
        href: '/workflows/history',
        icon: 'History',
        capability: 'canManageTeachers',
      },
      {
        title: 'Reports',
        description: 'Download and export school reports',
        href: '/reports',
        icon: 'FileBarChart',
        capability: 'canManageTeachers',
      },
      {
        title: 'Command center',
        description: 'Executive school health overview',
        href: '/enterprise',
        icon: 'LayoutDashboard',
        capability: 'canManageTeachers',
      },
      {
        title: 'Assistant',
        description: 'AI school assistant',
        href: '/assistant',
        icon: 'Bot',
        capability: 'isStaff',
      },
    ],
  },
  {
    label: 'People',
    modules: [
      {
        title: 'People analytics',
        description: 'Enrolment and staff insights',
        href: '/people/analytics',
        icon: 'BarChart3',
        capability: 'canManageStudents',
      },
      {
        title: 'Students',
        description: 'Browse and manage learners',
        href: '/students',
        icon: 'GraduationCap',
        capability: 'canManageStudents',
      },
      {
        title: 'Teachers',
        description: 'Staff records and profiles',
        href: '/teachers',
        icon: 'Users',
        capability: 'canManageTeachers',
      },
      {
        title: 'Guardians',
        description: 'Parents and emergency contacts',
        href: '/guardians',
        icon: 'UserCheck',
        capability: 'canManageStudents',
      },
      {
        title: 'Enrollment',
        description: 'Applications and intake pipeline',
        href: '/enrollment',
        icon: 'ClipboardList',
        capability: 'canManageStudents',
      },
    ],
  },
  {
    label: 'Academics',
    modules: [
      {
        title: 'Academics analytics',
        description: 'Attendance and assessment trends',
        href: '/academics/analytics',
        icon: 'BarChart3',
        capability: 'canManageTeachers',
      },
      {
        title: 'Classes',
        description: 'Class groups and setup',
        href: '/settings?tab=classes',
        icon: 'BookOpen',
        capability: 'canManageTeachers',
      },
      {
        title: 'Subjects',
        description: 'Curriculum subjects',
        href: '/settings?tab=subjects',
        icon: 'BookMarked',
        capability: 'canManageTeachers',
      },
      {
        title: 'Departments',
        description: 'Academic departments',
        href: '/settings?tab=departments',
        icon: 'Building2',
        capability: 'canManageTeachers',
      },
      {
        title: 'Grade levels',
        description: 'Year and grade structure',
        href: '/settings?tab=grade-levels',
        icon: 'Layers',
        capability: 'canManageTeachers',
      },
      {
        title: 'Grading scales',
        description: 'Mark bands and grade rules',
        href: '/settings?tab=grading',
        icon: 'SlidersHorizontal',
        capability: 'canManageTeachers',
      },
      {
        title: 'Rooms',
        description: 'Classrooms and venues',
        href: '/settings?tab=rooms',
        icon: 'DoorOpen',
        capability: 'canManageTeachers',
      },
      {
        title: 'Terms',
        description: 'Academic calendar terms',
        href: '/settings?tab=academic-setup',
        icon: 'Calendar',
        capability: 'canManageTeachers',
      },
      {
        title: 'Teacher assignments',
        description: 'Staff-to-class allocations',
        href: '/academics/teacher-assignments',
        icon: 'UserCog',
        capability: 'canManageTeachers',
      },
      {
        title: 'Class assignments',
        description: 'Subject-class mappings',
        href: '/academics/assignments',
        icon: 'ListChecks',
        capability: 'canManageTeachers',
      },
      {
        title: 'Gradebook',
        description: 'Enter and review marks',
        href: '/academics/grades',
        icon: 'NotebookPen',
        capability: 'canManageExaminations',
      },
      {
        title: 'Attendance',
        description: 'Daily class register',
        href: '/academics/attendance',
        icon: 'ClipboardCheck',
        capability: 'canManageStudents',
      },
      {
        title: 'Exams',
        description: 'Schedule and publish results',
        href: '/academics/exams',
        icon: 'FileText',
        capability: 'canManageExaminations',
      },
      {
        title: 'Class tests',
        description: 'Continuous assessment tests',
        href: '/academics/tests',
        icon: 'PenLine',
        capability: 'canManageExaminations',
      },
      {
        title: 'Timetable',
        description: 'Weekly class schedule',
        href: '/academics/timetable',
        icon: 'CalendarDays',
        capability: 'canManageTeachers',
      },
      {
        title: 'Holiday programs',
        description: 'Holiday school activities',
        href: '/academics/holiday-programs',
        icon: 'Sun',
        capability: 'canManageTeachers',
      },
    ],
  },
  {
    label: 'Finance',
    modules: [
      {
        title: 'Finance analytics',
        description: 'Collections and revenue trends',
        href: '/finance/analytics',
        icon: 'BarChart3',
        capability: 'canManageFinance',
      },
      {
        title: 'Finance overview',
        description: 'Daily collections snapshot',
        href: '/finance',
        icon: 'Wallet',
        capability: 'canManageFinance',
      },
      {
        title: 'Payments',
        description: 'Record fee collections',
        href: '/finance/payments',
        icon: 'CreditCard',
        capability: 'canManageFinance',
      },
      {
        title: 'Invoices',
        description: 'Bill students and track balances',
        href: '/finance/invoices',
        icon: 'Receipt',
        capability: 'canManageFinance',
      },
      {
        title: 'Fee structures',
        description: 'Configure fees by class',
        href: '/finance/fees',
        icon: 'Tags',
        capability: 'canManageFinance',
      },
      {
        title: 'Fee categories',
        description: 'Fee type groupings',
        href: '/finance/fee-categories',
        icon: 'FolderTree',
        capability: 'canManageFinance',
      },
      {
        title: 'Transactions',
        description: 'Ledger and journal activity',
        href: '/finance/transactions',
        icon: 'ArrowLeftRight',
        capability: 'canManageFinance',
      },
      {
        title: 'Payroll',
        description: 'Staff salary processing',
        href: '/finance/payroll',
        icon: 'Banknote',
        capability: 'canManageFinance',
      },
      {
        title: 'Aging report',
        description: 'Outstanding fees by age',
        href: '/finance/reports',
        icon: 'LineChart',
        capability: 'canManageFinance',
      },
    ],
  },
  {
    label: 'HR & Compliance',
    modules: [
      {
        title: 'HR analytics',
        description: 'Leave, discipline, and compliance',
        href: '/hr/analytics',
        icon: 'BarChart3',
        capability: 'canManageTeachers',
      },
      {
        title: 'Leave requests',
        description: 'Staff leave queue',
        href: '/hr/leave',
        icon: 'Palmtree',
        capability: 'canManageTeachers',
      },
      {
        title: 'Discipline',
        description: 'Student conduct records',
        href: '/hr/discipline',
        icon: 'ShieldAlert',
        capability: 'canManageStudents',
      },
      {
        title: 'Policies',
        description: 'School compliance policies',
        href: '/compliance',
        icon: 'Scale',
        capability: 'canManageTeachers',
      },
      {
        title: 'Incidents',
        description: 'Report and track incidents',
        href: '/compliance/incidents',
        icon: 'AlertTriangle',
        capability: 'canManageTeachers',
      },
      {
        title: 'Consent forms',
        description: 'Parent consent management',
        href: '/compliance/consent',
        icon: 'FileCheck',
        capability: 'canManageStudents',
      },
      {
        title: 'Audit trail',
        description: 'Who did what across the system',
        href: '/compliance/audit',
        icon: 'ScrollText',
        capability: 'canViewAuditLogs',
      },
    ],
  },
  {
    label: 'Communications',
    modules: [
      {
        title: 'Communications analytics',
        description: 'Messaging and engagement',
        href: '/communications/analytics',
        icon: 'BarChart3',
        capability: 'isStaff',
      },
      {
        title: 'Announcements',
        description: 'Broadcast to the school',
        href: '/communications/announcements',
        icon: 'Megaphone',
        capability: 'isStaff',
      },
      {
        title: 'Messages',
        description: 'Staff and parent inbox',
        href: '/communications/threads',
        icon: 'MessageSquare',
        capability: 'isStaff',
      },
    ],
  },
  {
    label: 'Operations',
    modules: [
      {
        title: 'Operations analytics',
        description: 'Campus and resource usage',
        href: '/operations/analytics',
        icon: 'BarChart3',
        capability: 'canManageTeachers',
      },
      {
        title: 'Inventory',
        description: 'Stock and supplies',
        href: '/operations/inventory',
        icon: 'Package',
        capability: 'canManageTeachers',
      },
      {
        title: 'Inventory sales',
        description: 'Point-of-sale stock sales',
        href: '/operations/inventory/sales',
        icon: 'ShoppingBag',
        capability: 'canManageTeachers',
      },
      {
        title: 'Procurement',
        description: 'Purchase requests',
        href: '/operations/procurement',
        icon: 'ShoppingCart',
        capability: 'canManageFinance',
      },
      {
        title: 'Vendors',
        description: 'Supplier directory',
        href: '/operations/procurement/vendors',
        icon: 'Truck',
        capability: 'canManageFinance',
      },
      {
        title: 'Library',
        description: 'Books and lending',
        href: '/operations/library',
        icon: 'Library',
        capability: 'canManageTeachers',
      },
      {
        title: 'Transport',
        description: 'Fleet and routes overview',
        href: '/operations/transport',
        icon: 'Bus',
        capability: 'canManageTeachers',
      },
      {
        title: 'Transport drivers',
        description: 'Driver records',
        href: '/operations/transport/drivers',
        icon: 'IdCard',
        capability: 'canManageTeachers',
      },
      {
        title: 'Transport routes',
        description: 'Route planning',
        href: '/operations/transport/routes',
        icon: 'Route',
        capability: 'canManageTeachers',
      },
      {
        title: 'Assets',
        description: 'Fixed asset register',
        href: '/operations/assets',
        icon: 'HardDrive',
        capability: 'canManageFinance',
      },
      {
        title: 'Hostels',
        description: 'Boarding and dormitories',
        href: '/operations/hostels',
        icon: 'Home',
        capability: 'canManageTeachers',
      },
      {
        title: 'Visitors',
        description: 'Sign-in register',
        href: '/operations/visitors',
        icon: 'UserCheck',
        capability: 'canManageTeachers',
      },
      {
        title: 'Health records',
        description: 'Student health clinic',
        href: '/operations/health',
        icon: 'HeartPulse',
        capability: 'canManageTeachers',
      },
      {
        title: 'Events',
        description: 'School calendar events',
        href: '/operations/events',
        icon: 'CalendarHeart',
        capability: 'canManageTeachers',
      },
      {
        title: 'School trips',
        description: 'Excursions with parent registration',
        href: '/operations/school-trips',
        icon: 'Bus',
        capability: 'canManageTeachers',
      },
    ],
  },
  {
    label: 'Enterprise',
    modules: [
      {
        title: 'Enterprise finance',
        description: 'Advanced finance modules',
        href: '/enterprise/finance',
        icon: 'Landmark',
        capability: 'canManageFinance',
      },
      {
        title: 'Instalment plans',
        description: 'Fee instalment schedules',
        href: '/enterprise/finance/instalments',
        icon: 'CalendarClock',
        capability: 'canManageFinance',
      },
      {
        title: 'Enterprise academic',
        description: 'Curriculum and promotion rules',
        href: '/enterprise/academic',
        icon: 'GraduationCap',
        capability: 'canManageTeachers',
      },
      {
        title: 'Enterprise exams',
        description: 'Advanced exam configuration',
        href: '/enterprise/exams',
        icon: 'FileStack',
        capability: 'canManageExaminations',
      },
      {
        title: 'Enterprise HR',
        description: 'Advanced HR workflows',
        href: '/enterprise/hr',
        icon: 'Briefcase',
        capability: 'canManageTeachers',
      },
      {
        title: 'Warnings',
        description: 'Staff and student warnings',
        href: '/enterprise/warnings',
        icon: 'AlertCircle',
        capability: 'canManageTeachers',
      },
      {
        title: 'Alumni',
        description: 'Former student records',
        href: '/enterprise/alumni',
        icon: 'UsersRound',
        capability: 'canManageTeachers',
      },
      {
        title: 'Campaigns',
        description: 'Fundraising and outreach',
        href: '/enterprise/campaigns',
        icon: 'Megaphone',
        capability: 'canManageTeachers',
      },
    ],
  },
  {
    label: 'Administration',
    modules: [
      {
        title: 'Users',
        description: 'System user accounts',
        href: '/admin/users',
        icon: 'UserCog',
        capability: 'canManageTeachers',
      },
      {
        title: 'Roles',
        description: 'Permissions and access',
        href: '/admin/roles',
        icon: 'KeyRound',
        capability: 'canManageTeachers',
      },
      {
        title: 'Settings',
        description: 'School profile and preferences',
        href: '/settings',
        icon: 'Settings',
        capability: 'canManageTeachers',
      },
      {
        title: 'Custom fields',
        description: 'Extra data fields for records',
        href: '/settings/custom-fields',
        icon: 'FormInput',
        capability: 'canManageTeachers',
      },
    ],
  },
]

export const EXAM_OFFICER_DASHBOARD_MODULE_GROUPS: DashboardModuleGroup[] = [
  {
    label: 'Examinations',
    modules: [
      {
        title: 'Exams',
        description: 'Schedule, approve, and publish examinations',
        href: '/academics/exams',
        icon: 'FileText',
        capability: 'canManageExaminations',
      },
      {
        title: 'Gradebook',
        description: 'Continuous assessment and mark entry',
        href: '/academics/grades',
        icon: 'BookOpen',
        capability: 'canManageExaminations',
      },
      {
        title: 'Class tests',
        description: 'Manage tests and short assessments',
        href: '/academics/tests',
        icon: 'NotebookPen',
        capability: 'canManageExaminations',
      },
      {
        title: 'Enterprise exams',
        description: 'Advanced examination workflows',
        href: '/enterprise/exams',
        icon: 'LayoutDashboard',
        capability: 'canManageExaminations',
      },
    ],
  },
  {
    label: 'Learners & context',
    modules: [
      {
        title: 'Students',
        description: 'Learner records under assessment',
        href: '/students',
        icon: 'GraduationCap',
        capability: 'canManageStudents',
      },
      {
        title: 'Attendance',
        description: 'Attendance linked to assessment periods',
        href: '/academics/attendance',
        icon: 'ClipboardCheck',
        capability: 'canManageStudents',
      },
      {
        title: 'Academics analytics',
        description: 'Performance and attendance trends',
        href: '/academics/analytics',
        icon: 'BarChart3',
        capability: ['canManageTeachers', 'canManageStudents', 'canManageExaminations'],
      },
      {
        title: 'Reports',
        description: 'Export academic reports',
        href: '/reports',
        icon: 'FileBarChart',
        capability: 'canManageTeachers',
      },
    ],
  },
  {
    label: 'Communication',
    modules: [
      {
        title: 'Messages',
        description: 'Coordinate with staff and parents',
        href: '/communications/threads',
        icon: 'MessageSquare',
        capability: 'isStaff',
      },
      {
        title: 'Announcements',
        description: 'Publish exam notices',
        href: '/communications/announcements',
        icon: 'Megaphone',
        capability: 'isStaff',
      },
      {
        title: 'Assistant',
        description: 'AI support for exam administration',
        href: '/assistant',
        icon: 'Bot',
        capability: 'isStaff',
      },
    ],
  },
]

export const STUDENT_DASHBOARD_MODULE_GROUPS: DashboardModuleGroup[] = [
  {
    label: 'My school',
    modules: [
      {
        title: 'Timetable',
        description: 'Your class weekly schedule',
        href: '/student/timetable',
        icon: 'CalendarDays',
      },
      {
        title: 'Performance',
        description: 'Continuous assessment by subject',
        href: '/student/performance',
        icon: 'BarChart3',
      },
      {
        title: 'Attendance',
        description: 'Your presence summary',
        href: '/student/attendance',
        icon: 'ClipboardCheck',
      },
      {
        title: 'Exams',
        description: 'Exam schedule and published results',
        href: '/student/exams',
        icon: 'FileText',
      },
      {
        title: 'Fees',
        description: 'Invoices and balances',
        href: '/student/fees',
        icon: 'Wallet',
      },
    ],
  },
]

export const PLATFORM_DASHBOARD_MODULE_GROUPS: DashboardModuleGroup[] = [
  {
    label: 'Platform',
    modules: [
      {
        title: 'Licenses',
        description: 'Issue and manage school licenses',
        href: '/platform/licenses',
        icon: 'Key',
        capability: 'isSuperAdmin',
      },
      {
        title: 'System health',
        description: 'Infrastructure monitoring',
        href: '/platform/health',
        icon: 'Activity',
        capability: 'isSuperAdmin',
      },
      {
        title: 'Operations',
        description: 'Jobs, queues, and live ops',
        href: '/platform/operations',
        icon: 'Server',
        capability: 'isSuperAdmin',
      },
      {
        title: 'API clients',
        description: 'External integrations',
        href: '/platform/api-clients',
        icon: 'Plug',
        capability: 'isSuperAdmin',
      },
      {
        title: 'Communications',
        description: 'Send and track platform-wide messages',
        href: '/platform/communications',
        icon: 'Megaphone',
        capability: 'isSuperAdmin',
      },
      {
        title: 'Signable documents',
        description: 'E-sign consent forms, policies, and agreements',
        href: '/platform/documents',
        icon: 'FileText',
        capability: 'isSuperAdmin',
      },
      {
        title: 'Scholarships',
        description: 'Platform scholarship programs',
        href: '/platform/scholarships',
        icon: 'Award',
        capability: 'isSuperAdmin',
      },
      {
        title: 'Refunds',
        description: 'Platform refund processing',
        href: '/platform/refunds',
        icon: 'RotateCcw',
        capability: 'isSuperAdmin',
      },
      {
        title: 'Staff tasks',
        description: 'Assign and track operational work across schools',
        href: '/platform/staff-tasks',
        icon: 'ListTodo',
        capability: 'isSuperAdmin',
      },
    ],
  },
]

export const PARENT_DASHBOARD_MODULE_GROUPS: DashboardModuleGroup[] = [
  {
    label: 'Parent portal',
    modules: [
      {
        title: 'My children',
        description: 'View your children\'s profiles',
        href: '/portal/children',
        icon: 'Users',
        capability: 'isParent',
      },
      {
        title: 'Messages',
        description: 'Chat with school staff',
        href: '/portal/hub?tab=messages',
        icon: 'MessageSquare',
        capability: 'isParent',
      },
      {
        title: 'Announcements',
        description: 'School news and updates',
        href: '/portal/hub?tab=announcements',
        icon: 'Megaphone',
        capability: 'isParent',
      },
      {
        title: 'Consent forms',
        description: 'Review and respond to forms',
        href: '/portal/hub?tab=consent',
        icon: 'FileCheck',
        capability: 'isParent',
      },
      {
        title: 'Notifications',
        description: 'Alerts and reminders',
        href: '/portal/hub?tab=notifications',
        icon: 'Bell',
        capability: 'isParent',
      },
      {
        title: 'School store',
        description: 'Order uniforms and stock',
        href: '/portal/hub?tab=store',
        icon: 'ShoppingBag',
        capability: 'isParent',
      },
      {
        title: 'School trips',
        description: 'Register for upcoming trips',
        href: '/portal/hub?tab=trips',
        icon: 'Bus',
        capability: 'isParent',
      },
    ],
  },
]
