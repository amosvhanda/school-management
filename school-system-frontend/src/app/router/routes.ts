import type { RouteRecordRaw } from 'vue-router'

const RegistryListPage = () => import('@/modules/shared/RegistryListPage.vue')

function listRoute(
  path: string,
  name: string,
  listKey: string,
  meta: Record<string, unknown> = {},
): RouteRecordRaw {
  return {
    path,
    name,
    component: RegistryListPage,
    meta: { listKey, ...meta },
  }
}

function staffListRoute(path: string, name: string, listKey: string, capability?: string): RouteRecordRaw {
  return listRoute(path, name, listKey, {
    capability: capability ?? 'isStaff',
  })
}

function parentListRoute(path: string, name: string, listKey: string): RouteRecordRaw {
  return listRoute(path, name, listKey, { roles: ['parent'] })
}

function platformListRoute(path: string, name: string, listKey: string): RouteRecordRaw {
  return listRoute(path, name, listKey, { roles: ['super_admin'] })
}

export const publicRoutes: RouteRecordRaw[] = [
  {
    path: 'login',
    name: 'login',
    component: () => import('@/modules/auth/views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: 'license/activate',
    name: 'license-activate',
    component: () => import('@/modules/auth/views/LicenseActivateView.vue'),
    meta: { requiresAuth: true },
  },
]

function sectionAnalyticsRoute(
  path: string,
  name: string,
  sectionKey: string,
  capability?: string | string[],
): RouteRecordRaw {
  return {
    path,
    name,
    component: () => import('@/modules/analytics/views/SectionAnalyticsView.vue'),
    meta: { sectionKey, capability: capability ?? 'isStaff' },
  }
}

export const staffRoutes: RouteRecordRaw[] = [
  {
    path: '',
    name: 'dashboard',
    component: () => import('@/modules/dashboard/views/DashboardView.vue'),
    meta: { capability: 'isStaff' },
  },
  staffListRoute('students', 'students', 'students', 'canManageStudents'),
  sectionAnalyticsRoute('people/analytics', 'people-analytics', 'people', 'canManageStudents'),
  staffListRoute('teachers', 'teachers', 'teachers', 'canManageTeachers'),
  staffListRoute('guardians', 'guardians', 'guardians', 'canManageStudents'),
  {
    path: 'enrollment',
    name: 'enrollment',
    component: () => import('@/modules/enrollment/views/EnrollmentView.vue'),
    meta: { capability: 'canManageStudents' },
  },
  staffListRoute('academics/setup', 'academics-setup', 'academics-setup', 'canManageTeachers'),
  sectionAnalyticsRoute('academics/analytics', 'academics-analytics', 'academics', [
    'canManageTeachers',
    'canManageStudents',
    'canEnterExamResults',
  ]),
  staffListRoute('academics/subjects', 'academics-subjects', 'academics-subjects', 'canManageTeachers'),
  staffListRoute('academics/departments', 'academics-departments', 'academics-departments', 'canManageTeachers'),
  staffListRoute('academics/grade-levels', 'academics-grade-levels', 'academics-grade-levels', 'canManageTeachers'),
  staffListRoute('academics/grading-scales', 'academics-grading-scales', 'academics-grading-scales', 'canManageTeachers'),
  staffListRoute('academics/rooms', 'academics-rooms', 'academics-rooms', 'canManageTeachers'),
  staffListRoute('academics/terms', 'academics-terms', 'academics-terms', 'canManageTeachers'),
  staffListRoute('academics/assignments', 'academics-assignments', 'academics-assignments', 'canManageTeachers'),
  staffListRoute('academics/tests', 'academics-tests', 'academics-tests', 'canManageExaminations'),
  staffListRoute('academics/teacher-assignments', 'academics-teacher-assignments', 'academics-teacher-assignments', 'canManageTeachers'),
  {
    path: 'academics/grades',
    name: 'academics-grades',
    component: () => import('@/modules/academics/views/GradebookView.vue'),
    meta: { capability: 'canManageExaminations' },
  },
  staffListRoute('academics/timetable', 'academics-timetable', 'academics-timetable', 'canManageTeachers'),
  {
    path: 'academics/exams',
    name: 'academics-exams',
    component: () => import('@/modules/academics/views/ExamsView.vue'),
    meta: { capability: ['canManageExaminations', 'canEnterExamResults'] },
  },
  {
    path: 'academics/attendance',
    name: 'academics-attendance',
    component: () => import('@/modules/academics/views/AttendanceRegisterView.vue'),
    meta: { capability: 'canManageStudents' },
  },
  staffListRoute('academics/holiday-programs', 'academics-holiday-programs', 'academics-holiday-programs', 'canManageTeachers'),
  {
    path: 'finance',
    name: 'finance',
    component: () => import('@/modules/finance/views/FinanceOverviewView.vue'),
    meta: { capability: 'canManageFinance' },
  },
  sectionAnalyticsRoute('finance/analytics', 'finance-analytics', 'finance', 'canManageFinance'),
  {
    path: 'finance/reports',
    name: 'finance-reports',
    component: () => import('@/modules/finance/views/FinanceReportsView.vue'),
    meta: { capability: 'canManageFinance' },
  },
  staffListRoute('finance/payments', 'finance-payments', 'finance-payments', 'canManageFinance'),
  staffListRoute('finance/invoices', 'finance-invoices', 'finance-invoices', 'canManageFinance'),
  staffListRoute('finance/fees', 'finance-fees', 'finance-fees', 'canManageFinance'),
  staffListRoute('finance/fee-categories', 'finance-fee-categories', 'finance-fee-categories', 'canManageFinance'),
  staffListRoute('finance/transactions', 'finance-transactions', 'finance-transactions', 'canManageFinance'),
  staffListRoute('finance/payroll', 'finance-payroll', 'finance-payroll', 'canManageFinance'),
  staffListRoute('operations/inventory', 'ops-inventory', 'ops-inventory', 'canManageTeachers'),
  sectionAnalyticsRoute('operations/analytics', 'operations-analytics', 'operations', 'canManageTeachers'),
  staffListRoute('operations/inventory/sales', 'ops-inventory-sales', 'ops-inventory-sales', 'canManageTeachers'),
  staffListRoute('operations/procurement', 'ops-procurement', 'ops-procurement', 'canManageFinance'),
  staffListRoute('operations/procurement/vendors', 'ops-procurement-vendors', 'ops-procurement-vendors', 'canManageFinance'),
  staffListRoute('operations/library', 'ops-library', 'ops-library', 'canManageTeachers'),
  staffListRoute('operations/transport', 'ops-transport', 'ops-transport', 'canManageTeachers'),
  staffListRoute('operations/transport/drivers', 'ops-transport-drivers', 'ops-transport-drivers', 'canManageTeachers'),
  staffListRoute('operations/transport/routes', 'ops-transport-routes', 'ops-transport-routes', 'canManageTeachers'),
  staffListRoute('operations/assets', 'ops-assets', 'ops-assets', 'canManageFinance'),
  staffListRoute('operations/hostels', 'ops-hostels', 'ops-hostels', 'canManageTeachers'),
  staffListRoute('operations/visitors', 'ops-visitors', 'ops-visitors', 'canManageTeachers'),
  staffListRoute('operations/health', 'ops-health', 'ops-health', 'canManageTeachers'),
  staffListRoute('operations/events', 'ops-events', 'ops-events', 'canManageTeachers'),
  {
    path: 'communications/announcements',
    name: 'comms-announcements',
    component: () => import('@/modules/communications/views/AnnouncementsView.vue'),
    meta: { capability: 'isStaff' },
  },
  sectionAnalyticsRoute('communications/analytics', 'communications-analytics', 'communications', 'isStaff'),
  {
    path: 'communications/threads',
    name: 'comms-threads',
    component: () => import('@/modules/communications/views/CommunicationThreadsView.vue'),
    meta: { capability: 'isStaff' },
  },
  {
    path: 'hr/leave',
    name: 'hr-leave',
    component: () => import('@/modules/hr/views/LeaveRequestsView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  sectionAnalyticsRoute('hr/analytics', 'hr-analytics', 'hr', 'canManageTeachers'),
  staffListRoute('hr/discipline', 'hr-discipline', 'hr-discipline', 'canManageStudents'),
  staffListRoute('compliance', 'compliance', 'compliance', 'canManageTeachers'),
  staffListRoute('compliance/incidents', 'compliance-incidents', 'compliance-incidents', 'canManageTeachers'),
  staffListRoute('compliance/consent', 'compliance-consent', 'compliance-consent', 'canManageStudents'),
  {
    path: 'compliance/audit',
    name: 'compliance-audit',
    component: () => import('@/modules/compliance/views/AuditTrailView.vue'),
    meta: { capability: 'canViewAuditLogs' },
  },
  {
    path: 'compliance/login-history',
    redirect: { name: 'compliance-audit', query: { tab: 'login' } },
  },
  {
    path: 'reports',
    name: 'reports',
    component: () => import('@/modules/reports/views/ReportsView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'admin/users',
    name: 'admin-users',
    component: () => import('@/modules/admin/views/UsersView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'admin/roles',
    name: 'admin-roles',
    component: () => import('@/modules/admin/views/RolesView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'settings',
    name: 'settings',
    component: () => import('@/modules/settings/views/SettingsView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'settings/school',
    redirect: { name: 'settings' },
  },
  staffListRoute('settings/custom-fields', 'settings-custom-fields', 'settings-custom-fields', 'canManageTeachers'),
  {
    path: 'assistant',
    name: 'assistant',
    component: () => import('@/modules/assistant/views/AssistantView.vue'),
    meta: { capability: 'isStaff' },
  },
  {
    path: 'enterprise',
    name: 'enterprise',
    component: () => import('@/modules/enterprise/views/CommandCenterView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  staffListRoute('enterprise/finance', 'enterprise-finance', 'enterprise-finance', 'canManageFinance'),
  staffListRoute('enterprise/finance/instalments', 'enterprise-finance-instalments', 'enterprise-finance-instalments', 'canManageFinance'),
  staffListRoute('enterprise/academic', 'enterprise-academic', 'enterprise-academic', 'canManageTeachers'),
  staffListRoute('enterprise/exams', 'enterprise-exams', 'enterprise-exams', 'canManageExaminations'),
  staffListRoute('enterprise/hr', 'enterprise-hr', 'enterprise-hr', 'canManageTeachers'),
  staffListRoute('enterprise/warnings', 'enterprise-warnings', 'enterprise-warnings', 'canManageTeachers'),
  staffListRoute('enterprise/alumni', 'enterprise-alumni', 'enterprise-alumni', 'canManageTeachers'),
  staffListRoute('enterprise/campaigns', 'enterprise-campaigns', 'enterprise-campaigns', 'canManageTeachers'),
  {
    path: 'workflows',
    name: 'workflows',
    component: () => import('@/modules/workflows/views/WorkflowsView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'workflows/history',
    name: 'workflows-history',
    redirect: { name: 'workflows', query: { tab: 'history' } },
  },
  {
    path: 'analytics',
    name: 'analytics',
    component: () => import('@/modules/enterprise/views/AnalyticsView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'students/:id',
    name: 'student-detail',
    component: () => import('@/modules/students/views/StudentDetailView.vue'),
    meta: { capability: 'canManageStudents' },
  },
]

export const parentRoutes: RouteRecordRaw[] = [
  {
    path: 'portal',
    name: 'parent-dashboard',
    component: () => import('@/modules/parent-portal/views/ParentDashboardView.vue'),
    meta: { roles: ['parent'] },
  },
  parentListRoute('portal/children', 'portal-children', 'portal-children'),
  {
    path: 'portal/children/:id',
    name: 'parent-child-detail',
    component: () => import('@/modules/parent-portal/views/ParentChildDetailView.vue'),
    meta: { roles: ['parent'] },
  },
  parentListRoute('portal/announcements', 'portal-announcements', 'portal-announcements'),
  {
    path: 'portal/messages',
    name: 'portal-messages',
    component: () => import('@/modules/parent-portal/views/ParentMessagesView.vue'),
    meta: { roles: ['parent'] },
  },
  {
    path: 'portal/consent',
    name: 'portal-consent',
    component: () => import('@/modules/parent-portal/views/ParentConsentView.vue'),
    meta: { roles: ['parent'] },
  },
  {
    path: 'portal/notifications',
    name: 'portal-notifications',
    component: () => import('@/modules/parent-portal/views/ParentNotificationsView.vue'),
    meta: { roles: ['parent'] },
  },
]

export const studentRoutes: RouteRecordRaw[] = [
  {
    path: 'student',
    name: 'student-dashboard',
    component: () => import('@/modules/student-portal/views/StudentDashboardView.vue'),
    meta: { roles: ['student'] },
  },
  {
    path: 'student/performance',
    name: 'student-performance',
    component: () => import('@/modules/student-portal/views/StudentDashboardView.vue'),
    meta: { roles: ['student'] },
  },
  {
    path: 'student/attendance',
    name: 'student-attendance',
    component: () => import('@/modules/student-portal/views/StudentDashboardView.vue'),
    meta: { roles: ['student'] },
  },
  {
    path: 'student/exams',
    name: 'student-exams',
    component: () => import('@/modules/student-portal/views/StudentDashboardView.vue'),
    meta: { roles: ['student'] },
  },
  {
    path: 'student/fees',
    name: 'student-fees',
    component: () => import('@/modules/student-portal/views/StudentDashboardView.vue'),
    meta: { roles: ['student'] },
  },
]

export const platformRoutes: RouteRecordRaw[] = [
  {
    path: 'platform',
    name: 'platform-dashboard',
    component: () => import('@/modules/platform/views/PlatformDashboardView.vue'),
    meta: { roles: ['super_admin'] },
  },
  {
    path: 'platform/licenses',
    name: 'platform-licenses',
    component: () => import('@/modules/platform/views/PlatformLicensesView.vue'),
    meta: { roles: ['super_admin'] },
  },
  {
    path: 'platform/communications',
    name: 'platform-communications',
    component: () => import('@/modules/platform/views/PlatformCommunicationsView.vue'),
    meta: { roles: ['super_admin'] },
  },
  {
    path: 'platform/health',
    name: 'platform-health',
    component: () => import('@/modules/platform/views/PlatformStatusView.vue'),
    meta: { roles: ['super_admin'] },
  },
  {
    path: 'platform/operations',
    name: 'platform-operations',
    component: () => import('@/modules/platform/views/PlatformStatusView.vue'),
    meta: { roles: ['super_admin'] },
  },
  platformListRoute('platform/api-clients', 'platform-api-clients', 'platform-api-clients'),
  platformListRoute('platform/documents', 'platform-documents', 'platform-documents'),
  platformListRoute('platform/scholarships', 'platform-scholarships', 'platform-scholarships'),
  platformListRoute('platform/refunds', 'platform-refunds', 'platform-refunds'),
  platformListRoute('platform/staff-tasks', 'platform-staff-tasks', 'platform-staff-tasks'),
]
