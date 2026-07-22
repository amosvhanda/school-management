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
  {
    path: 'students',
    name: 'students',
    redirect: redirectToHubTab('people', 'students'),
  },
  sectionAnalyticsRoute('people/analytics', 'people-analytics', 'people', 'canManageStudents'),
  {
    path: 'teachers',
    name: 'teachers',
    redirect: redirectToHubTab('people', 'teachers'),
  },
  {
    path: 'guardians',
    name: 'guardians',
    redirect: redirectToHubTab('people', 'guardians'),
  },
  {
    path: 'enrollment',
    name: 'enrollment',
    redirect: redirectToHubTab('people', 'enrollment'),
  },
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
    component: () => import('@/modules/finance/views/FinanceHubView.vue'),
    meta: { capability: 'canManageFinance' },
  },
  sectionAnalyticsRoute('finance/analytics', 'finance-analytics', 'finance', 'canManageFinance'),
  {
    path: 'finance/cash-flow',
    name: 'finance-cash-flow',
    redirect: redirectToHubTab('finance', 'cash-flow'),
  },
  {
    path: 'finance/reports',
    name: 'finance-reports',
    redirect: redirectToHubTab('finance', 'aging'),
  },
  {
    path: 'finance/payments',
    name: 'finance-payments',
    redirect: redirectToHubTab('finance', 'payments'),
  },
  {
    path: 'finance/invoices',
    name: 'finance-invoices',
    redirect: redirectToHubTab('finance', 'invoices'),
  },
  {
    path: 'finance/fees',
    name: 'finance-fees',
    redirect: redirectToHubTab('finance', 'fees'),
  },
  {
    path: 'finance/fee-categories',
    name: 'finance-fee-categories',
    redirect: redirectToHubTab('finance', 'fees'),
  },
  {
    path: 'finance/transactions',
    name: 'finance-transactions',
    redirect: redirectToHubTab('finance', 'transactions'),
  },
  {
    path: 'finance/payroll',
    name: 'finance-payroll',
    redirect: redirectToHubTab('finance', 'payroll'),
  },
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
  {
    path: 'operations/inventory',
    name: 'ops-inventory',
    redirect: redirectToHubTab('operations', 'inventory'),
  },
  {
    path: 'operations/inventory/sales',
    name: 'ops-inventory-sales',
    redirect: redirectToHubTab('operations', 'inventory'),
  },
  {
    path: 'operations/procurement',
    name: 'ops-procurement',
    redirect: redirectToHubTab('finance', 'procurement'),
  },
  {
    path: 'operations/procurement/vendors',
    name: 'ops-procurement-vendors',
    redirect: redirectToHubTab('finance', 'procurement'),
  },
  {
    path: 'operations/library',
    name: 'ops-library',
    redirect: redirectToHubTab('operations', 'library'),
  },
  {
    path: 'operations/transport',
    name: 'ops-transport',
    redirect: redirectToHubTab('operations', 'transport'),
  },
  {
    path: 'operations/transport/drivers',
    name: 'ops-transport-drivers',
    redirect: redirectToHubTab('operations', 'transport'),
  },
  {
    path: 'operations/transport/routes',
    name: 'ops-transport-routes',
    redirect: redirectToHubTab('operations', 'transport'),
  },
  {
    path: 'operations/assets',
    name: 'ops-assets',
    redirect: redirectToHubTab('finance', 'assets'),
  },
  {
    path: 'operations/hostels',
    name: 'ops-hostels',
    redirect: redirectToHubTab('operations', 'hostels'),
  },
  {
    path: 'operations/visitors',
    name: 'ops-visitors',
    redirect: redirectToHubTab('operations', 'visitors'),
  },
  {
    path: 'operations/health',
    name: 'ops-health',
    redirect: redirectToHubTab('operations', 'health'),
  },
  {
    path: 'operations/events',
    name: 'ops-events',
    redirect: redirectToHubTab('operations', 'events'),
  },
  {
    path: 'operations/school-trips',
    name: 'ops-school-trips',
    redirect: redirectToHubTab('operations', 'trips'),
  },
  {
    path: 'communications',
    name: 'communications',
    component: () => import('@/modules/communications/views/CommunicationsHubView.vue'),
    meta: { capability: 'isStaff' },
  },
  {
    path: 'communications/announcements',
    name: 'comms-announcements',
    redirect: redirectToHubTab('communications', 'announcements', 'announcements'),
  },
  sectionAnalyticsRoute('communications/analytics', 'communications-analytics', 'communications', 'isStaff'),
  {
    path: 'communications/threads',
    name: 'comms-threads',
    redirect: redirectToHubTab('communications', 'messages', 'announcements'),
  },
  {
    path: 'hr',
    name: 'hr',
    component: () => import('@/modules/hr/views/HrHubView.vue'),
    meta: { capability: ['canManageTeachers', 'canManageStudents', 'canViewAuditLogs'] },
  },
  {
    path: 'hr/leave',
    name: 'hr-leave',
    redirect: redirectToHubTab('hr', 'leave', 'leave'),
  },
  sectionAnalyticsRoute('hr/analytics', 'hr-analytics', 'hr', 'canManageTeachers'),
  {
    path: 'hr/discipline',
    name: 'hr-discipline',
    redirect: redirectToHubTab('hr', 'discipline', 'leave'),
  },
  {
    path: 'compliance',
    name: 'compliance',
    redirect: redirectToHubTab('hr', 'policies', 'leave'),
  },
  staffListRoute('compliance/incidents', 'compliance-incidents', 'compliance-incidents', 'canManageTeachers'),
  staffListRoute('compliance/consent', 'compliance-consent', 'compliance-consent', 'canManageStudents'),
  {
    path: 'compliance/audit',
    name: 'compliance-audit',
    redirect: redirectToHubTab('hr', 'audit', 'leave'),
  },
  {
    path: 'compliance/login-history',
    redirect: redirectToHubTab('hr', 'audit', 'leave'),
  },
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
  {
    path: 'admin/users',
    name: 'admin-users',
    redirect: redirectToHubTab('admin', 'users', 'users'),
  },
  {
    path: 'admin/roles',
    name: 'admin-roles',
    redirect: redirectToHubTab('admin', 'roles', 'users'),
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
  },
  {
    path: 'portal/messages',
    name: 'portal-messages',
    redirect: redirectToHubTab('portal-hub', 'messages', 'messages'),
  },
  {
    path: 'portal/consent',
    name: 'portal-consent',
    redirect: redirectToHubTab('portal-hub', 'consent', 'messages'),
  },
  {
    path: 'portal/store',
    name: 'portal-store',
    redirect: redirectToHubTab('portal-hub', 'store', 'messages'),
  },
  {
    path: 'portal/trips',
    name: 'portal-trips',
    redirect: redirectToHubTab('portal-hub', 'trips', 'messages'),
  },
  {
    path: 'portal/notifications',
    name: 'portal-notifications',
    redirect: redirectToHubTab('portal-hub', 'notifications', 'messages'),
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
