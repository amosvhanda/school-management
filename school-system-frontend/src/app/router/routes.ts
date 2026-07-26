import type { RouteRecordRaw } from 'vue-router'
import { redirectToSchoolSetup } from '@/modules/settings/school-setup-links'
import { redirectToHubTab } from '@/lib/module-hub'

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

function staffHubRedirect(
  path: string,
  name: string | undefined,
  hubRouteName: string,
  tabId: string,
  defaultTabId: string,
  capability: string | string[],
  extraQuery?: Record<string, string>,
): RouteRecordRaw {
  return {
    path,
    ...(name ? { name } : {}),
    redirect: redirectToHubTab(hubRouteName, tabId, defaultTabId, extraQuery),
    meta: { capability },
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
    path: 'forgot-password',
    name: 'forgot-password',
    component: () => import('@/modules/auth/views/ForgotPasswordView.vue'),
    meta: { guest: true },
  },
  {
    path: 'reset-password',
    name: 'reset-password',
    component: () => import('@/modules/auth/views/ResetPasswordView.vue'),
    meta: { guest: true },
  },
  {
    path: 'legal/terms',
    name: 'legal-terms',
    component: () => import('@/modules/auth/views/LegalDocumentView.vue'),
    meta: { document: 'terms' },
  },
  {
    path: 'legal/privacy',
    name: 'legal-privacy',
    component: () => import('@/modules/auth/views/LegalDocumentView.vue'),
    meta: { document: 'privacy' },
  },
  {
    path: 'terms/accept',
    name: 'platform-terms-accept',
    component: () => import('@/modules/auth/views/PlatformTermsAcceptView.vue'),
    meta: { requiresAuth: true, allowWithoutTerms: true },
  },
  {
    path: 'license/activate',
    name: 'license-activate',
    component: () => import('@/modules/auth/views/LicenseActivateView.vue'),
    meta: { requiresAuth: true, allowWithoutTerms: true },
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
  {
    path: 'people',
    name: 'people',
    component: () => import('@/modules/people/views/PeopleHubView.vue'),
    meta: { capability: ['canManageStudents', 'canManageTeachers'] },
  },
  staffHubRedirect('students', 'students', 'people', 'students', 'students', 'canManageStudents'),
  sectionAnalyticsRoute('people/analytics', 'people-analytics', 'people', 'canManageStudents'),
  staffHubRedirect('teachers', 'teachers', 'people', 'teachers', 'students', 'canManageTeachers'),
  staffHubRedirect('guardians', 'guardians', 'people', 'guardians', 'students', 'canManageTeachers'),
  staffHubRedirect('enrollment', 'enrollment', 'people', 'enrollment', 'students', 'canManageTeachers'),
  {
    path: 'academics/classes',
    name: 'academics-setup',
    redirect: redirectToSchoolSetup('classes'),
  },
  {
    path: 'academics/setup',
    redirect: redirectToSchoolSetup('classes'),
  },
  {
    path: 'academics/streams',
    name: 'academics-streams',
    redirect: redirectToSchoolSetup('streams'),
  },
  {
    path: 'academics/houses',
    name: 'academics-houses',
    redirect: redirectToSchoolSetup('houses'),
  },
  {
    path: 'academics/subject-packages',
    name: 'academics-subject-packages',
    redirect: redirectToSchoolSetup('subject-packages'),
  },
  sectionAnalyticsRoute('academics/analytics', 'academics-analytics', 'academics', [
    'canManageTeachers',
    'canManageStudents',
    'canEnterExamResults',
  ]),
  {
    path: 'academics/subjects',
    name: 'academics-subjects',
    redirect: redirectToSchoolSetup('subjects'),
  },
  {
    path: 'academics/departments',
    name: 'academics-departments',
    redirect: redirectToSchoolSetup('departments'),
  },
  {
    path: 'academics/grade-levels',
    name: 'academics-grade-levels',
    redirect: redirectToSchoolSetup('grade-levels'),
  },
  {
    path: 'academics/grading-scales',
    name: 'academics-grading-scales',
    redirect: redirectToSchoolSetup('grading'),
  },
  {
    path: 'academics/rooms',
    name: 'academics-rooms',
    redirect: redirectToSchoolSetup('rooms'),
  },
  {
    path: 'academics/terms',
    name: 'academics-terms',
    redirect: redirectToSchoolSetup('academic-setup'),
  },
  {
    path: 'academics/assignments',
    name: 'academics-assignments',
    component: () => import('@/modules/shared/RegistryListPage.vue'),
    props: { listKey: 'academics-assignments' },
    meta: { capability: ['canManageTeachers', 'canEnterExamResults'] },
  },
  {
    path: 'academics/tests',
    name: 'academics-tests',
    component: () => import('@/modules/shared/RegistryListPage.vue'),
    props: { listKey: 'academics-tests' },
    meta: { capability: ['canManageExaminations', 'canEnterExamResults'] },
  },
  staffListRoute('academics/teacher-assignments', 'academics-teacher-assignments', 'academics-teacher-assignments', 'canManageTeachers'),
  {
    path: 'academics/grades',
    name: 'academics-grades',
    component: () => import('@/modules/academics/views/GradebookView.vue'),
    meta: { capability: ['canManageExaminations', 'canEnterExamResults'] },
  },
  {
    path: 'academics/timetable',
    name: 'academics-timetable',
    component: () => import('@/modules/academics/views/TimetableManagementView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'academics/my-timetable',
    name: 'academics-my-timetable',
    component: () => import('@/modules/academics/views/MyTimetableView.vue'),
    meta: { roles: ['teacher'] },
  },
  {
    path: 'academics/exams',
    name: 'academics-exams',
    component: () => import('@/modules/academics/views/ExamsView.vue'),
    meta: { capability: ['canManageExaminations', 'canEnterExamResults'] },
  },
  staffListRoute('academics/exam-schedules', 'academics-exam-schedules', 'academics-exam-schedules', 'canManageExaminations'),
  {
    path: 'academics/certificates',
    name: 'academics-certificates',
    component: () => import('@/modules/academics/views/CertificatesHubView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'academics/attendance',
    name: 'academics-attendance',
    component: () => import('@/modules/academics/views/AttendanceRegisterView.vue'),
    meta: { capability: 'canManageStudents' },
  },
  {
    path: 'teaching',
    name: 'teaching',
    component: () => import('@/modules/teaching/views/TeachingHubView.vue'),
    meta: { capability: 'isStaff', roles: ['teacher', 'admin', 'school_admin'] },
  },
  staffListRoute('academics/holiday-programs', 'academics-holiday-programs', 'academics-holiday-programs', 'canManageTeachers'),
  {
    path: 'finance',
    name: 'finance',
    component: () => import('@/modules/finance/views/FinanceHubView.vue'),
    meta: { capability: 'canManageFinance' },
  },
  sectionAnalyticsRoute('finance/analytics', 'finance-analytics', 'finance', 'canManageFinance'),
  staffHubRedirect('finance/cash-flow', 'finance-cash-flow', 'finance', 'cash-flow', 'overview', 'canManageFinance'),
  staffHubRedirect('finance/reports', 'finance-reports', 'finance', 'aging', 'overview', 'canManageFinance'),
  staffHubRedirect('finance/payments', 'finance-payments', 'finance', 'payments', 'overview', 'canManageFinance'),
  staffHubRedirect('finance/invoices', 'finance-invoices', 'finance', 'invoices', 'overview', 'canManageFinance'),
  staffHubRedirect('finance/fees', 'finance-fees', 'finance', 'fees', 'overview', 'canManageFinance'),
  staffHubRedirect('finance/fee-categories', 'finance-fee-categories', 'finance', 'fees', 'overview', 'canManageFinance'),
  staffHubRedirect('finance/transactions', 'finance-transactions', 'finance', 'transactions', 'overview', 'canManageFinance'),
  staffHubRedirect('finance/payroll', 'finance-payroll', 'finance', 'payroll', 'overview', 'canManageFinance'),
  {
    path: 'operations',
    name: 'operations',
    component: () => import('@/modules/operations/views/OperationsHubView.vue'),
    meta: {
      capability: [
        'canManageInventory',
        'canManageLibrary',
        'canManageTransport',
        'canManageReception',
        'canManageTeachers',
      ],
    },
  },
  sectionAnalyticsRoute('operations/analytics', 'operations-analytics', 'operations', [
    'canManageInventory',
    'canManageLibrary',
    'canManageTransport',
    'canManageReception',
  ]),
  staffHubRedirect('operations/inventory', 'ops-inventory', 'operations', 'inventory', 'inventory', 'canManageInventory'),
  staffHubRedirect('operations/inventory/sales', 'ops-inventory-sales', 'operations', 'inventory', 'inventory', 'canManageInventory'),
  staffHubRedirect('operations/procurement', 'ops-procurement', 'finance', 'procurement', 'overview', 'canManageFinance'),
  staffHubRedirect('operations/procurement/vendors', 'ops-procurement-vendors', 'finance', 'procurement', 'overview', 'canManageFinance'),
  staffHubRedirect('operations/library', 'ops-library', 'operations', 'library', 'inventory', 'canManageLibrary'),
  staffHubRedirect('operations/transport', 'ops-transport', 'operations', 'transport', 'inventory', 'canManageTransport'),
  staffHubRedirect('operations/transport/drivers', 'ops-transport-drivers', 'operations', 'transport', 'inventory', 'canManageTransport'),
  staffHubRedirect('operations/transport/routes', 'ops-transport-routes', 'operations', 'transport', 'inventory', 'canManageTransport'),
  staffHubRedirect('operations/assets', 'ops-assets', 'finance', 'assets', 'overview', 'canManageFinance'),
  staffHubRedirect('operations/hostels', 'ops-hostels', 'operations', 'hostels', 'inventory', 'canManageTeachers'),
  staffHubRedirect('operations/visitors', 'ops-visitors', 'operations', 'visitors', 'inventory', 'canManageReception'),
  staffHubRedirect('operations/health', 'ops-health', 'operations', 'health', 'inventory', 'canManageTeachers'),
  staffHubRedirect('operations/events', 'ops-events', 'operations', 'events', 'inventory', 'canManageTeachers'),
  staffHubRedirect('operations/school-trips', 'ops-school-trips', 'operations', 'trips', 'inventory', 'canManageTeachers'),
  {
    path: 'communications',
    name: 'communications',
    component: () => import('@/modules/communications/views/CommunicationsHubView.vue'),
    meta: { capability: 'isStaff' },
  },
  staffHubRedirect('communications/announcements', 'comms-announcements', 'communications', 'announcements', 'announcements', 'isStaff'),
  sectionAnalyticsRoute('communications/analytics', 'communications-analytics', 'communications', 'isStaff'),
  staffHubRedirect('communications/threads', 'comms-threads', 'communications', 'messages', 'announcements', 'isStaff'),
  {
    path: 'hr',
    name: 'hr',
    component: () => import('@/modules/hr/views/HrHubView.vue'),
    meta: { capability: ['canManageTeachers', 'canManageStudents', 'canViewAuditLogs'] },
  },
  staffHubRedirect('hr/leave', 'hr-leave', 'hr', 'leave', 'employees', 'canManageTeachers'),
  sectionAnalyticsRoute('hr/analytics', 'hr-analytics', 'hr', 'canManageTeachers'),
  staffHubRedirect('hr/discipline', 'hr-discipline', 'hr', 'discipline', 'employees', 'canManageStudents'),
  // Policies also live under HR hub (?tab=policies); keep /compliance as a direct URL.
  staffListRoute('compliance', 'compliance', 'compliance', 'canManageTeachers'),
  staffHubRedirect('compliance/incidents', 'compliance-incidents', 'hr', 'incidents', 'employees', 'canManageTeachers'),
  staffHubRedirect('compliance/consent', 'compliance-consent', 'hr', 'consent', 'employees', 'canManageTeachers'),
  staffHubRedirect('compliance/audit', 'compliance-audit', 'hr', 'audit', 'employees', 'canViewAuditLogs'),
  staffHubRedirect(
    'compliance/login-history',
    undefined,
    'hr',
    'audit',
    'employees',
    'canViewAuditLogs',
    { auditView: 'login' },
  ),
  {
    path: 'reports',
    name: 'reports',
    component: () => import('@/modules/reports/views/ReportsView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  {
    path: 'admin',
    name: 'admin',
    component: () => import('@/modules/admin/views/AdminHubView.vue'),
    meta: { capability: 'canManageTeachers' },
  },
  staffHubRedirect('admin/users', 'admin-users', 'admin', 'users', 'users', 'canManageTeachers'),
  staffHubRedirect('admin/roles', 'admin-roles', 'admin', 'roles', 'users', 'canManageTeachers'),
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
    path: 'profile',
    name: 'my-profile',
    component: () => import('@/modules/profile/views/MyProfileView.vue'),
    meta: { roles: ['admin', 'teacher', 'finance', 'accounts', 'examination_officer', 'parent', 'student'] },
  },
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
  {
    path: 'portal/hub',
    name: 'portal-hub',
    component: () => import('@/modules/parent-portal/views/ParentHubView.vue'),
    meta: { roles: ['parent'] },
  },
  {
    path: 'portal/announcements',
    name: 'portal-announcements',
    redirect: redirectToHubTab('portal-hub', 'announcements', 'messages'),
    meta: { roles: ['parent'] },
  },
  {
    path: 'portal/messages',
    name: 'portal-messages',
    redirect: redirectToHubTab('portal-hub', 'messages', 'messages'),
    meta: { roles: ['parent'] },
  },
  {
    path: 'portal/consent',
    name: 'portal-consent',
    redirect: redirectToHubTab('portal-hub', 'consent', 'messages'),
    meta: { roles: ['parent'] },
  },
  {
    path: 'portal/store',
    name: 'portal-store',
    redirect: redirectToHubTab('portal-hub', 'store', 'messages'),
    meta: { roles: ['parent'] },
  },
  {
    path: 'portal/trips',
    name: 'portal-trips',
    redirect: redirectToHubTab('portal-hub', 'trips', 'messages'),
    meta: { roles: ['parent'] },
  },
  {
    path: 'portal/notifications',
    name: 'portal-notifications',
    redirect: redirectToHubTab('portal-hub', 'notifications', 'messages'),
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
  {
    path: 'student/assignments',
    name: 'student-assignments',
    component: () => import('@/modules/student-portal/views/StudentDashboardView.vue'),
    meta: { roles: ['student'] },
  },
  {
    path: 'student/announcements',
    name: 'student-announcements',
    component: () => import('@/modules/student-portal/views/StudentDashboardView.vue'),
    meta: { roles: ['student'] },
  },
  {
    path: 'student/timetable',
    name: 'student-timetable',
    component: () => import('@/modules/academics/views/MyTimetableView.vue'),
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
  {
    path: 'platform/documents',
    name: 'platform-documents',
    component: () => import('@/modules/platform/views/PlatformDocumentsView.vue'),
    meta: { roles: ['super_admin'] },
  },
  platformListRoute('platform/scholarships', 'platform-scholarships', 'platform-scholarships'),
  platformListRoute('platform/refunds', 'platform-refunds', 'platform-refunds'),
  {
    path: 'platform/staff-tasks',
    name: 'platform-staff-tasks',
    component: () => import('@/modules/platform/views/PlatformStaffTasksView.vue'),
    meta: { roles: ['super_admin'] },
  },
]
