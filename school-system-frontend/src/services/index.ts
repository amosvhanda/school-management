import type { ListQueryParams } from '@/types/api'
import { endpoints as e } from './endpoints'
import { fetchList, fetchOne, createRecord, updateRecord, deleteRecord } from './dashboard.service'

export { fetchList, fetchOne, createRecord, updateRecord, deleteRecord } from './dashboard.service'
export * from './api.service'
export { endpoints } from './endpoints'

/**
 * List-page and relation field endpoints — single source of truth from endpoints.ts.
 * Do not hardcode API paths in views or components; import from here or api.service.
 */
export const moduleEndpoints = {
  students: e.students.list,
  teachers: e.teachers.list,
  guardians: e.guardians.list,
  enrollment: e.enrollment.list,
  classes: e.classes.list,
  subjects: e.subjects.list,
  departments: e.departments.list,
  gradeLevels: e.gradeLevels.list,
  gradingScales: e.gradingScales.list,
  rooms: e.rooms.list,
  terms: e.terms.list,
  assignments: e.assignments.list,
  tests: e.tests.list,
  teacherAssignments: e.teacherAssignments.list,
  /** Gradebook uses class-scoped routes; no GET /grades list. */
  grades: e.grades.store,
  timetable: e.timetable.list,
  exams: e.exams.list,
  attendance: e.attendance.list,
  holidayPrograms: e.holidayPrograms.list,
  payments: e.payments.list,
  invoices: e.invoices.list,
  transactions: e.transactions.list,
  feeStructures: e.feeStructures.list,
  feeCategories: e.feeCategories.list,
  payroll: e.payroll.list,
  inventoryItems: e.inventory.items,
  inventorySales: e.inventory.sales,
  procurementRequisitions: e.procurement.requisitions,
  procurementVendors: e.procurement.vendors,
  libraryBooks: e.library.books,
  libraryLoans: e.library.borrow,
  transportVehicles: e.transport.vehicles,
  transportDrivers: e.transport.drivers,
  transportRoutes: e.transport.routes,
  assets: e.assets.list,
  hostels: e.hostels.list,
  visitors: e.visitors.list,
  healthVisits: e.health.visits,
  events: e.events.list,
  announcements: e.announcements.list,
  threads: e.communications.threads,
  leaveRequests: e.leaveRequests.list,
  discipline: e.discipline.list,
  compliancePolicies: e.compliance.policies,
  complianceIncidents: e.compliance.incidents,
  consentForms: e.consentForms.list,
  auditLogs: e.auditLogs.list,
  auditLoginHistory: e.auditLogs.loginHistory,
  reports: e.reports.export,
  financeSummary: e.finance.summary,
  users: e.users.list,
  roles: e.roles.list,
  school: e.school.show,
  settingsSchool: e.settings.school,
  customFields: e.settings.customFields,
  assistantConversations: e.assistant.conversations,
  workflowsPending: e.workflows.pending,
  workflowsHistory: e.workflows.history,
  analyticsInsights: e.analytics.insights,
  enterpriseCommandCenter: e.enterprise.commandCenter,
  enterpriseFinance: e.enterprise.finance.accounts,
  enterpriseFinanceInstalments: e.enterprise.finance.instalmentPlans,
  enterpriseAcademic: e.enterprise.academic.calendar,
  enterpriseExams: e.enterprise.exams.questionBank,
  enterpriseHr: e.enterprise.hr.performanceReviews,
  enterpriseEarlyWarnings: e.enterprise.intelligence.earlyWarnings,
  enterpriseAlumni: e.enterprise.intelligence.alumni,
  enterpriseCampaigns: e.enterprise.intelligence.campaigns,
  adminLicenses: e.license.admin.list,
  platformSystemHealth: e.platform.systemHealth,
  platformOperationsLive: e.platform.operationsLive,
  platformApiClients: e.platform.apiClients,
  platformDocuments: e.platform.documents,
  platformScholarships: e.platform.scholarships,
  platformRefunds: e.platform.refunds,
  platformStaffTasks: e.platform.staffTasks,
  parentPortalChildren: e.parentPortal.children,
  parentPortalAnnouncements: e.parentPortal.announcements,
  parentPortalThreads: e.parentPortal.threads,
  parentPortalConsent: e.parentPortal.consentForms,
  parentPortalNotifications: e.parentPortal.notifications,
} as const

export function listModule(key: keyof typeof moduleEndpoints, params?: ListQueryParams) {
  return fetchList(moduleEndpoints[key], params)
}

/** @deprecated Use schoolApi from api.service */
export const settingsApi = {
  school: () => fetchOne(e.school.show),
  updateSchool: (payload: Record<string, unknown>) => updateRecord(e.school.show, payload),
  schoolSettings: () => fetchOne(e.settings.school),
  updateSchoolSettings: (settings: Array<{ group: string; key: string; value: unknown; type?: string; is_public?: boolean }>) =>
    updateRecord(e.settings.school, { settings }),
  terminology: () => fetchOne(e.settings.terminology),
  updateTerminology: (payload: Record<string, unknown>) => updateRecord(e.settings.terminology, payload),
  customFields: () => fetchList(e.settings.customFields),
  createCustomField: (payload: Record<string, unknown>) => createRecord(e.settings.customFields, payload),
  deleteCustomField: (id: number | string) => deleteRecord(e.settings.customField(id)),
}
