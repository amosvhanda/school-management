import {
  announcementColumns,
  assetColumns,
  attendanceColumns,
  auditColumns,
  complianceColumns,
  consentColumns,
  defaultColumns,
  disciplineColumns,
  enrollmentColumns,
  eventColumns,
  feeStructureColumns,
  feeCategoryColumns,
  genericColumns,
  guardianColumns,
  healthColumns,
  holidayProgramColumns,
  hostelColumns,
  inventoryColumns,
  invoiceColumns,
  leaveColumns,
  libraryColumns,
  paymentColumns,
  payrollColumns,
  portalChildColumns,
  procurementColumns,
  studentColumns,
  teacherColumns,
  threadColumns,
  timetableColumns,
  transportColumns,
  userColumns,
  visitorColumns,
  workflowColumns,
} from '@/modules/shared/columns'
import type { ColumnDef } from '@tanstack/vue-table'
import { endpoints } from '@/services/endpoints'
import { moduleEndpoints } from '@/services/index'

export interface ListPageConfig {
  title: string
  description?: string
  endpoint: string
  /** When create uses a different path than list (e.g. visitors check-in). */
  createEndpoint?: string
  columns: ColumnDef<Record<string, unknown>, unknown>[]
}

export const listPageRegistry: Record<string, ListPageConfig> = {
  students: { title: 'Students', description: 'Enrolled students', endpoint: moduleEndpoints.students, columns: studentColumns },
  teachers: { title: 'Teachers', description: 'Manage teaching staff records', endpoint: moduleEndpoints.teachers, columns: teacherColumns },
  guardians: { title: 'Guardians', description: 'Manage parent and guardian records', endpoint: moduleEndpoints.guardians, columns: guardianColumns },
  enrollment: { title: 'Enrollment Applications', endpoint: moduleEndpoints.enrollment, columns: enrollmentColumns },

  'academics-setup': { title: 'Classes', description: 'Class and grade setup', endpoint: moduleEndpoints.classes, columns: defaultColumns(['name', 'grade_level', 'capacity', 'status']) },
  'academics-streams': { title: 'Streams', description: 'Academic streams (e.g. Sciences, Arts)', endpoint: moduleEndpoints.streams, columns: defaultColumns(['name', 'code', 'is_active']) },
  'academics-houses': { title: 'Houses', description: 'Pastoral houses', endpoint: moduleEndpoints.houses, columns: defaultColumns(['name', 'code', 'color', 'is_active']) },
  'academics-subject-packages': { title: 'Subject packages', description: 'Subjects by grade and stream', endpoint: moduleEndpoints.subjectPackages, columns: defaultColumns(['grade_level_id', 'subject_id', 'stream_id', 'is_core']) },
  'academics-subjects': { title: 'Subjects', endpoint: moduleEndpoints.subjects, columns: defaultColumns(['name', 'code', 'status']) },
  'academics-departments': { title: 'Departments', endpoint: moduleEndpoints.departments, columns: defaultColumns(['name', 'head_teacher_id', 'status']) },
  'academics-grade-levels': { title: 'Grade Levels', endpoint: moduleEndpoints.gradeLevels, columns: defaultColumns(['name', 'order', 'status']) },
  'academics-grading-scales': { title: 'Grading Scales', endpoint: moduleEndpoints.gradingScales, columns: defaultColumns(['name', 'min_score', 'max_score', 'grade']) },
  'academics-rooms': { title: 'Rooms', endpoint: moduleEndpoints.rooms, columns: defaultColumns(['name', 'building', 'capacity', 'status']) },
  'academics-terms': { title: 'Terms', endpoint: moduleEndpoints.terms, columns: defaultColumns(['name', 'academic_year', 'start_date', 'end_date', 'status']) },
  'academics-assignments': { title: 'Assignments', endpoint: moduleEndpoints.assignments, columns: defaultColumns(['title', 'class_id', 'subject_id', 'due_date', 'status']) },
  'academics-tests': { title: 'Tests', endpoint: moduleEndpoints.tests, columns: defaultColumns(['name', 'class_id', 'subject_id', 'test_date', 'status']) },
  'academics-teacher-assignments': { title: 'Teacher Assignments', endpoint: moduleEndpoints.teacherAssignments, columns: defaultColumns(['teacher_id', 'class_id', 'subject_id', 'status']) },
  'academics-grades': { title: 'Gradebook', endpoint: moduleEndpoints.classes, columns: genericColumns },
  'academics-timetable': { title: 'Timetable', endpoint: moduleEndpoints.timetable, columns: timetableColumns },
  'academics-exams': { title: 'Exams', endpoint: moduleEndpoints.exams, columns: genericColumns },
  'academics-attendance': { title: 'Attendance', endpoint: moduleEndpoints.attendance, columns: attendanceColumns },
  'academics-holiday-programs': { title: 'Holiday Programs', endpoint: moduleEndpoints.holidayPrograms, columns: holidayProgramColumns },

  finance: { title: 'Finance Overview', endpoint: moduleEndpoints.financeSummary, columns: genericColumns },
  'finance-payments': { title: 'Payments', endpoint: moduleEndpoints.payments, columns: paymentColumns },
  'finance-invoices': { title: 'Invoices', endpoint: moduleEndpoints.invoices, columns: invoiceColumns },
  'finance-fees': {
    title: 'Fee Structures',
    endpoint: moduleEndpoints.feeStructures,
    columns: feeStructureColumns,
    description: 'Amounts charged per class, linked to fee categories.',
  },
  'finance-fee-categories': {
    title: 'Fee Categories',
    endpoint: moduleEndpoints.feeCategories,
    columns: feeCategoryColumns,
    description: 'Shared categories used by fee structures (tuition, levies, exams…).',
  },
  'finance-transactions': { title: 'Transactions', endpoint: moduleEndpoints.transactions, columns: defaultColumns(['date', 'type', 'amount', 'reference', 'status']) },
  'finance-payroll': { title: 'Payroll', endpoint: moduleEndpoints.payroll, columns: payrollColumns },

  'ops-inventory': { title: 'Inventory Items', endpoint: moduleEndpoints.inventoryItems, columns: inventoryColumns },
  'ops-inventory-sales': { title: 'Inventory Sales', endpoint: moduleEndpoints.inventorySales, columns: defaultColumns(['item_id', 'quantity', 'total', 'created_at']) },
  'ops-procurement': { title: 'Procurement Requisitions', endpoint: moduleEndpoints.procurementRequisitions, columns: procurementColumns },
  'ops-procurement-vendors': { title: 'Procurement Vendors', endpoint: moduleEndpoints.procurementVendors, columns: defaultColumns(['name', 'contact_person', 'phone', 'status']) },
  'ops-library': { title: 'Library Books', endpoint: moduleEndpoints.libraryBooks, columns: libraryColumns },
  'ops-transport': { title: 'Transport Vehicles', endpoint: moduleEndpoints.transportVehicles, columns: transportColumns },
  'ops-transport-drivers': { title: 'Transport Drivers', endpoint: moduleEndpoints.transportDrivers, columns: defaultColumns(['name', 'license_number', 'phone', 'status']) },
  'ops-transport-routes': { title: 'Transport Routes', endpoint: moduleEndpoints.transportRoutes, columns: defaultColumns(['name', 'vehicle_id', 'driver_id', 'status']) },
  'ops-assets': { title: 'Assets', endpoint: moduleEndpoints.assets, columns: assetColumns },
  'ops-hostels': { title: 'Hostels', endpoint: moduleEndpoints.hostels, columns: hostelColumns },
  'ops-visitors': {
    title: 'Visitors',
    endpoint: moduleEndpoints.visitors,
    createEndpoint: endpoints.visitors.checkIn,
    columns: visitorColumns,
  },
  'ops-health': { title: 'Clinic Visits', endpoint: moduleEndpoints.healthVisits, columns: healthColumns },
  'ops-events': { title: 'Events', endpoint: moduleEndpoints.events, columns: eventColumns },

  'comms-announcements': { title: 'Announcements', endpoint: moduleEndpoints.announcements, columns: announcementColumns },
  'comms-threads': { title: 'Message Threads', endpoint: moduleEndpoints.threads, columns: threadColumns },

  'hr-leave': { title: 'Leave Requests', endpoint: moduleEndpoints.leaveRequests, columns: leaveColumns },
  'hr-discipline': { title: 'Disciplinary Records', endpoint: moduleEndpoints.discipline, columns: disciplineColumns },

  compliance: { title: 'Compliance Policies', endpoint: moduleEndpoints.compliancePolicies, columns: complianceColumns },
  'compliance-incidents': { title: 'Compliance Incidents', endpoint: moduleEndpoints.complianceIncidents, columns: defaultColumns(['category', 'severity', 'description', 'created_at']) },
  'compliance-consent': { title: 'Consent Forms', endpoint: moduleEndpoints.consentForms, columns: consentColumns },
  'compliance-audit': { title: 'Audit Trail', endpoint: moduleEndpoints.auditLogs, columns: auditColumns },
  'compliance-login-history': { title: 'Login History', endpoint: moduleEndpoints.auditLoginHistory, columns: defaultColumns(['user_id', 'ip_address', 'created_at']) },

  reports: { title: 'Reports', endpoint: moduleEndpoints.reports, columns: genericColumns },

  'admin-users': { title: 'Users', endpoint: moduleEndpoints.users, columns: userColumns },
  'admin-roles': { title: 'Roles', endpoint: moduleEndpoints.roles, columns: defaultColumns(['name', 'slug']) },

  settings: { title: 'Settings', endpoint: moduleEndpoints.settingsSchool, columns: genericColumns },
  'settings-custom-fields': { title: 'Custom Fields', endpoint: moduleEndpoints.customFields, columns: defaultColumns(['entity_type', 'field_name', 'field_type', 'created_at']) },

  assistant: { title: 'Assistant Conversations', endpoint: moduleEndpoints.assistantConversations, columns: defaultColumns(['title', 'created_at', 'updated_at']) },

  workflows: { title: 'Pending Workflows', endpoint: moduleEndpoints.workflowsPending, columns: workflowColumns },
  'workflows-history': { title: 'Workflow History', endpoint: moduleEndpoints.workflowsHistory, columns: workflowColumns },

  analytics: { title: 'Analytics Insights', endpoint: moduleEndpoints.analyticsInsights, columns: genericColumns },
  enterprise: { title: 'Command Center', endpoint: moduleEndpoints.enterpriseCommandCenter, columns: genericColumns },

  'enterprise-finance': { title: 'Enterprise Finance Accounts', endpoint: moduleEndpoints.enterpriseFinance, columns: defaultColumns(['code', 'name', 'type', 'balance']) },
  'enterprise-finance-instalments': { title: 'Instalment Plans', endpoint: moduleEndpoints.enterpriseFinanceInstalments, columns: defaultColumns(['name', 'student_id', 'total', 'status']) },
  'enterprise-academic': { title: 'Academic Calendar', endpoint: moduleEndpoints.enterpriseAcademic, columns: defaultColumns(['title', 'start_date', 'end_date', 'type']) },
  'enterprise-exams': { title: 'Question Bank', endpoint: moduleEndpoints.enterpriseExams, columns: defaultColumns(['question', 'subject_id', 'difficulty', 'created_at']) },
  'enterprise-hr': { title: 'Performance Reviews', endpoint: moduleEndpoints.enterpriseHr, columns: defaultColumns(['employee_id', 'period', 'rating', 'created_at']) },
  'enterprise-warnings': { title: 'Early Warnings', endpoint: moduleEndpoints.enterpriseEarlyWarnings, columns: defaultColumns(['student_id', 'type', 'severity', 'created_at']) },
  'enterprise-alumni': { title: 'Alumni', endpoint: moduleEndpoints.enterpriseAlumni, columns: defaultColumns(['full_name', 'graduation_year', 'email', 'status']) },
  'enterprise-campaigns': { title: 'Campaigns', endpoint: moduleEndpoints.enterpriseCampaigns, columns: defaultColumns(['name', 'audience', 'status', 'created_at']) },

  'platform-licenses': { title: 'Licenses', endpoint: moduleEndpoints.adminLicenses, columns: genericColumns },
  'platform-health': { title: 'System Health', endpoint: moduleEndpoints.platformSystemHealth, columns: genericColumns },
  'platform-operations': { title: 'Live Operations', endpoint: moduleEndpoints.platformOperationsLive, columns: genericColumns },
  'platform-api-clients': { title: 'API Clients', endpoint: moduleEndpoints.platformApiClients, columns: defaultColumns(['name', 'client_id', 'status', 'created_at']) },
  'platform-documents': { title: 'Signable documents', endpoint: moduleEndpoints.platformDocuments, columns: defaultColumns(['title', 'type', 'status', 'created_at']) },
  'platform-scholarships': { title: 'Scholarships', endpoint: moduleEndpoints.platformScholarships, columns: defaultColumns(['name', 'amount', 'status', 'created_at']) },
  'platform-refunds': { title: 'Refunds', endpoint: moduleEndpoints.platformRefunds, columns: defaultColumns(['student_id', 'amount', 'status', 'created_at']) },
  'platform-staff-tasks': { title: 'Staff tasks', endpoint: moduleEndpoints.platformStaffTasks, columns: defaultColumns(['title', 'assignee_id', 'due_date', 'status']) },

  'portal-children': { title: 'My Children', endpoint: moduleEndpoints.parentPortalChildren, columns: portalChildColumns },
  'portal-announcements': { title: 'Announcements', endpoint: moduleEndpoints.parentPortalAnnouncements, columns: announcementColumns },
  'portal-messages': { title: 'Messages', endpoint: moduleEndpoints.parentPortalThreads, columns: threadColumns },
  'portal-consent': { title: 'Consent Forms', endpoint: moduleEndpoints.parentPortalConsent, columns: consentColumns },
  'portal-notifications': { title: 'Notifications', endpoint: moduleEndpoints.parentPortalNotifications, columns: defaultColumns(['title', 'type', 'read_at', 'created_at']) },
}
