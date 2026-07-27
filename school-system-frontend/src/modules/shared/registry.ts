import {
  announcementColumns,
  assetColumns,
  attendanceColumns,
  auditColumns,
  complianceColumns,
  complianceIncidentColumns,
  consentColumns,
  dateColumn,
  dateTimeColumn,
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
  inventorySaleColumns,
  invoiceColumns,
  leaveColumns,
  libraryColumns,
  nestedColumn,
  paymentColumns,
  payrollColumns,
  portalChildColumns,
  procurementColumns,
  schoolTripColumns,
  procurementVendorColumns,
  statusColumn,
  studentColumns,
  teacherColumns,
  termColumns,
  textColumn,
  threadColumns,
  timetableColumns,
  transactionColumns,
  transportColumns,
  transportDriverColumns,
  transportRouteColumns,
  userColumns,
  viewActionColumn,
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
  students: { title: 'Students', description: 'Enrolled students — admission numbers are auto-assigned (SCHOOLCODE-YEAR-####)', endpoint: moduleEndpoints.students, columns: studentColumns },
  teachers: { title: 'Teachers', description: 'Manage teaching staff records', endpoint: moduleEndpoints.teachers, columns: teacherColumns },
  guardians: { title: 'Guardians', description: 'Manage parent and guardian records', endpoint: moduleEndpoints.guardians, columns: guardianColumns },
  enrollment: { title: 'Enrollment Applications', endpoint: moduleEndpoints.enrollment, columns: enrollmentColumns },

  'academics-setup': {
    title: 'Classes',
    description: 'Class groups from grade level and stream (e.g. Form 4A2)',
    endpoint: moduleEndpoints.classes,
    columns: [
      ...defaultColumns(['name']),
      { id: 'grade_level', header: 'Grade level', cell: ({ row }) => {
        const grade = row.original.grade_level ?? row.original.gradeLevel
        return grade && typeof grade === 'object' ? String((grade as Record<string, unknown>).name ?? '—') : '—'
      } },
      { id: 'stream', header: 'Stream', cell: ({ row }) => {
        const stream = row.original.stream
        if (stream && typeof stream === 'object') {
          const name = String((stream as Record<string, unknown>).name ?? '').trim()
          const code = String((stream as Record<string, unknown>).code ?? '').trim()
          if (code && code.toLowerCase() !== name.toLowerCase()) return `${name} (${code})`
          return name || code || '—'
        }
        return '—'
      } },
      ...defaultColumns(['capacity']),
      { id: 'teacher', header: 'Class teacher', cell: ({ row }) => {
        const teacher = row.original.teacher
        return teacher && typeof teacher === 'object' ? String((teacher as Record<string, unknown>).name ?? '—') : '—'
      } },
      ...defaultColumns(['status']),
    ],
  },
  'academics-streams': { title: 'Streams', description: 'Academic streams (e.g. Sciences, Arts)', endpoint: moduleEndpoints.streams, columns: defaultColumns(['name', 'code', 'description', 'is_active']) },
  'academics-houses': {
    title: 'Houses',
    description: 'Pastoral houses',
    endpoint: moduleEndpoints.houses,
    columns: [
      ...defaultColumns(['name', 'code', 'color']),
      { id: 'teacher', header: 'House teacher', cell: ({ row }) => {
        const teacher = row.original.teacher
        return teacher && typeof teacher === 'object' ? String((teacher as Record<string, unknown>).full_name ?? (teacher as Record<string, unknown>).name ?? '—') : '—'
      } },
      ...defaultColumns(['is_active']),
    ],
  },
  'academics-subject-packages': {
    title: 'Subject packages',
    description: 'Subjects by grade and stream',
    endpoint: moduleEndpoints.subjectPackages,
    columns: [
      nestedColumn('Grade level', 'grade_level', 'name'),
      {
        id: 'subject',
        header: 'Subject',
        cell: ({ row }) => {
          const subject = row.original.subject
          if (!subject || typeof subject !== 'object') return '—'
          const name = String((subject as Record<string, unknown>).name ?? '').trim()
          const code = String((subject as Record<string, unknown>).code ?? '').trim()
          if (name && code) return `${name} (${code})`
          return name || code || '—'
        },
      },
      nestedColumn('Stream', 'stream', 'name'),
      { id: 'is_core', header: 'Core', cell: ({ row }) => (row.original.is_core ? 'Core' : 'Elective') },
    ],
  },
  'academics-subjects': { title: 'Subjects', endpoint: moduleEndpoints.subjects, columns: defaultColumns(['name', 'code', 'is_active']) },
  'academics-departments': {
    title: 'Departments',
    endpoint: moduleEndpoints.departments,
    columns: [
      ...defaultColumns(['name', 'code']),
      { id: 'head_teacher', header: 'Head', cell: ({ row }) => {
        const head = row.original.headTeacher ?? row.original.head_teacher
        return head && typeof head === 'object' ? String((head as Record<string, unknown>).name ?? '—') : '—'
      } },
      ...defaultColumns(['is_active']),
    ],
  },
  'academics-grade-levels': { title: 'Grade Levels', endpoint: moduleEndpoints.gradeLevels, columns: defaultColumns(['name', 'code', 'order', 'is_active']) },
  'academics-grading-scales': { title: 'Grading Scales', endpoint: moduleEndpoints.gradingScales, columns: defaultColumns(['grade', 'min_score', 'max_score', 'description']) },
  'academics-rooms': { title: 'Rooms', endpoint: moduleEndpoints.rooms, columns: defaultColumns(['name', 'code', 'type', 'location', 'capacity', 'is_active']) },
  'academics-terms': {
    title: 'Terms',
    description: 'Academic calendar terms — set one current term for gradebook, exams, and reports.',
    endpoint: moduleEndpoints.terms,
    columns: termColumns,
  },
  'academics-assignments': {
    title: 'Assignments',
    endpoint: moduleEndpoints.assignments,
    columns: [
      textColumn('Title', 'title'),
      textColumn('Class', 'class_name'),
      textColumn('Subject', 'subject'),
      textColumn('Teacher', 'teacher_name'),
      dateColumn('Due', 'due_date'),
      statusColumn(),
    ],
  },
  'academics-tests': {
    title: 'Tests',
    endpoint: moduleEndpoints.tests,
    columns: [
      textColumn('Name', 'name'),
      nestedColumn('Class', 'class', 'name'),
      nestedColumn('Subject', 'subject', 'name'),
      nestedColumn('Teacher', 'teacher', 'name'),
      dateColumn('Test date', 'test_date'),
      textColumn('Total marks', 'total_marks'),
    ],
  },
  'academics-teacher-assignments': {
    title: 'Teacher Assignments',
    endpoint: moduleEndpoints.teacherAssignments,
    columns: [
      nestedColumn('Teacher', 'teacher', 'name'),
      nestedColumn('Class', 'class_model', 'name'),
      nestedColumn('Grade level', 'grade_level', 'name'),
      nestedColumn('Subject', 'subject', 'name'),
      textColumn('Role', 'role'),
      { id: 'is_active', header: 'Status', cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active') },
    ],
  },
  'academics-grades': { title: 'Gradebook', endpoint: moduleEndpoints.classes, columns: genericColumns },
  'academics-timetable': { title: 'Timetable', endpoint: moduleEndpoints.timetable, columns: timetableColumns },
  'academics-exams': { title: 'Exams', endpoint: moduleEndpoints.exams, columns: genericColumns },
  'academics-exam-schedules': {
    title: 'Exam Schedules',
    description: 'Timetable slots for exams by class, subject, and room.',
    endpoint: moduleEndpoints.examSchedules,
    columns: [
      nestedColumn('Exam', 'exam', 'name'),
      nestedColumn('Class', 'class_model', 'name'),
      nestedColumn('Subject', 'subject', 'name'),
      dateTimeColumn('Starts', 'starts_at'),
      dateTimeColumn('Ends', 'ends_at'),
      textColumn('Duration (min)', 'duration_minutes'),
      textColumn('Invigilator', 'invigilator'),
    ],
  },
  'academics-certificate-templates': {
    title: 'Certificate Templates',
    description: 'Reusable certificate layouts with merge placeholders.',
    endpoint: moduleEndpoints.certificateTemplates,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Type', 'certificate_type'),
      textColumn('Title', 'title'),
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
    ],
  },
  'academics-school-certificates': {
    title: 'Issued Certificates',
    description: 'Certificates issued to students from templates.',
    endpoint: moduleEndpoints.schoolCertificates,
    columns: [
      textColumn('Title', 'title'),
      textColumn('Type', 'certificate_type'),
      nestedColumn('Student', 'student', 'full_name'),
      nestedColumn('Template', 'template', 'name'),
      textColumn('Verification', 'verification_code'),
      dateColumn('Issued', 'issued_at'),
      {
        id: 'revoked',
        header: 'Status',
        cell: ({ row }) => (row.original.revoked_at ? 'Revoked' : 'Valid'),
      },
    ],
  },
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
  'finance-fee-groups': {
    title: 'Fee Groups',
    description: 'Bundle fee categories for packaging on invoices and structures.',
    endpoint: moduleEndpoints.feeGroups,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Description', 'description'),
      {
        id: 'categories_count',
        header: 'Categories',
        cell: ({ row }) => String(row.original.categories_count ?? 0),
      },
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
      textColumn('Order', 'order'),
    ],
  },
  'finance-fee-discounts': {
    title: 'Fee Discounts',
    description: 'Percentage or fixed discounts by fee and student category.',
    endpoint: moduleEndpoints.feeDiscounts,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Type', 'discount_type'),
      textColumn('Value', 'value'),
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
      dateColumn('Starts', 'starts_on'),
      dateColumn('Ends', 'ends_on'),
    ],
  },
  'finance-income-heads': {
    title: 'Income Heads',
    description: 'Chart-of-accounts style labels for money coming in.',
    endpoint: moduleEndpoints.incomeHeads,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Code', 'code'),
      textColumn('Description', 'description'),
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
    ],
  },
  'finance-expense-heads': {
    title: 'Expense Heads',
    description: 'Chart-of-accounts style labels for money going out.',
    endpoint: moduleEndpoints.expenseHeads,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Code', 'code'),
      textColumn('Description', 'description'),
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
    ],
  },
  'finance-transactions': {
    title: 'Transactions',
    description:
      'School ledger of money in and out — fee collections, payroll expenses, and related movements.',
    endpoint: moduleEndpoints.transactions,
    columns: transactionColumns,
  },
  'finance-income': {
    title: 'Income',
    description: 'Manual income ledger entries classified by income head.',
    endpoint: moduleEndpoints.transactions,
    columns: [
      dateColumn('Date', 'date'),
      textColumn('Description', 'description'),
      nestedColumn('Income head', 'income_head', 'name'),
      textColumn('Amount', 'amount'),
      textColumn('Method', 'payment_method'),
      textColumn('Currency', 'currency'),
    ],
  },
  'finance-expense': {
    title: 'Expense',
    description: 'Manual expense ledger entries classified by expense head.',
    endpoint: moduleEndpoints.transactions,
    columns: [
      dateColumn('Date', 'date'),
      textColumn('Description', 'description'),
      nestedColumn('Expense head', 'expense_head', 'name'),
      textColumn('Amount', 'amount'),
      textColumn('Method', 'payment_method'),
      textColumn('Currency', 'currency'),
    ],
  },
  'finance-payroll': {
    title: 'Payroll',
    description:
      'Generate monthly payslips, adjust pending amounts if needed, then Process each row to record payment. Each Process posts an expense to Transactions.',
    endpoint: moduleEndpoints.payroll,
    columns: payrollColumns,
  },

  'ops-inventory': {
    title: 'Inventory Items',
    description: 'Uniforms, books, and supplies — track stock, pricing, and reorder levels.',
    endpoint: moduleEndpoints.inventoryItems,
    columns: inventoryColumns,
  },
  'ops-inventory-sales': { title: 'Inventory Sales', endpoint: moduleEndpoints.inventorySales, columns: inventorySaleColumns },
  'ops-procurement': {
    title: 'Spend requests',
    description:
      'Request money → approve in Workflows → Record payment. Nothing should leave the school till without this path (except payroll).',
    endpoint: moduleEndpoints.procurementRequisitions,
    columns: procurementColumns,
  },
  'ops-procurement-vendors': { title: 'Procurement Vendors', endpoint: moduleEndpoints.procurementVendors, columns: procurementVendorColumns },
  'ops-library': { title: 'Library Books', endpoint: moduleEndpoints.libraryBooks, columns: libraryColumns },
  'ops-library-members': {
    title: 'Library Members',
    description: 'Students, staff, and external borrowers registered with the library.',
    endpoint: moduleEndpoints.libraryMembers,
    columns: [
      textColumn('Member #', 'member_number'),
      textColumn('Name', 'name'),
      textColumn('Type', 'member_type'),
      textColumn('Linked ID', 'member_id'),
      textColumn('Email', 'email'),
      textColumn('Phone', 'phone'),
      statusColumn(),
      dateColumn('Joined', 'joined_on'),
      viewActionColumn('library-member-detail'),
    ],
  },
  'ops-library-loans': {
    title: 'Library Loans',
    description: 'Books currently on loan and return history.',
    endpoint: moduleEndpoints.libraryLoans,
    createEndpoint: endpoints.library.borrow,
    columns: [
      nestedColumn('Book', 'book', 'title'),
      nestedColumn('Member', 'member', 'name'),
      nestedColumn('Student', 'student', 'first_name'),
      dateColumn('Borrowed', 'borrowed_at'),
      dateColumn('Due', 'due_at'),
      dateColumn('Returned', 'returned_at'),
      statusColumn(),
      textColumn('Fine', 'fine_amount'),
    ],
  },
  'ops-transport': {
    title: 'Transport Vehicles',
    description: 'School buses and vans. Manage drivers and routes from the Operations menu.',
    endpoint: moduleEndpoints.transportVehicles,
    columns: transportColumns,
  },
  'ops-transport-drivers': {
    title: 'Transport Drivers',
    description: 'Drivers assigned to school transport routes.',
    endpoint: moduleEndpoints.transportDrivers,
    columns: transportDriverColumns,
  },
  'ops-transport-routes': {
    title: 'Transport Routes',
    description: 'Pickup routes linked to a vehicle and driver.',
    endpoint: moduleEndpoints.transportRoutes,
    columns: transportRouteColumns,
  },
  'ops-assets': { title: 'Assets', endpoint: moduleEndpoints.assets, columns: assetColumns },
  'ops-hostels': { title: 'Hostels', endpoint: moduleEndpoints.hostels, columns: hostelColumns },
  'ops-visitors': {
    title: 'Visitors',
    description: 'Front-desk visitor log — check visitors in and out.',
    endpoint: moduleEndpoints.visitors,
    createEndpoint: endpoints.visitors.checkIn,
    columns: visitorColumns,
  },
  'ops-health': { title: 'Clinic Visits', endpoint: moduleEndpoints.healthVisits, columns: healthColumns },
  'ops-events': { title: 'Events', endpoint: moduleEndpoints.events, columns: eventColumns },
  'ops-school-trips': {
    title: 'School trips',
    description: 'Create trips with fees and capacity. Parents register from the portal; the fee is invoiced to the student account.',
    endpoint: moduleEndpoints.schoolTrips,
    columns: schoolTripColumns,
  },

  'comms-announcements': { title: 'Announcements', endpoint: moduleEndpoints.announcements, columns: announcementColumns },
  'comms-threads': { title: 'Message Threads', endpoint: moduleEndpoints.threads, columns: threadColumns },

  'hr-leave': { title: 'Leave Requests', endpoint: moduleEndpoints.leaveRequests, columns: leaveColumns },
  'hr-leave-types': {
    title: 'Leave Types',
    description: 'Paid and unpaid leave categories with default day allowances.',
    endpoint: moduleEndpoints.leaveTypes,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Code', 'code'),
      textColumn('Default days', 'default_days'),
      {
        id: 'is_paid',
        header: 'Paid',
        cell: ({ row }) => (row.original.is_paid ? 'Yes' : 'No'),
      },
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
    ],
  },
  'hr-designations': {
    title: 'Designations',
    description: 'Job titles for non-teaching and teaching staff.',
    endpoint: moduleEndpoints.designations,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Code', 'code'),
      textColumn('Description', 'description'),
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
    ],
  },
  'hr-employees': {
    title: 'Employees',
    description: 'Non-teaching staff records, designations, and employment status.',
    endpoint: moduleEndpoints.employees,
    columns: [
      textColumn('Employee #', 'employee_number'),
      textColumn('Name', 'name'),
      textColumn('Email', 'email'),
      textColumn('Phone', 'phone'),
      nestedColumn('Designation', 'designation', 'name'),
      textColumn('Employment', 'employment_type'),
      statusColumn(),
      viewActionColumn('employee-detail'),
    ],
  },
  'people-student-categories': {
    title: 'Student Categories',
    description: 'Groupings used for fee discounts and reporting (e.g. boarder, day scholar).',
    endpoint: moduleEndpoints.studentCategories,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Code', 'code'),
      textColumn('Description', 'description'),
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
      textColumn('Order', 'order'),
    ],
  },
  'settings-currencies': {
    title: 'Currencies',
    description: 'School currencies for fees, payroll, and reporting.',
    endpoint: moduleEndpoints.schoolCurrencies,
    columns: [
      textColumn('Code', 'code'),
      textColumn('Name', 'name'),
      textColumn('Symbol', 'symbol'),
      {
        id: 'is_default',
        header: 'Default',
        cell: ({ row }) => (row.original.is_default ? 'Yes' : 'No'),
      },
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
    ],
  },
  'settings-languages': {
    title: 'Languages',
    description: 'Preferred languages available for school communications and UI preference.',
    endpoint: moduleEndpoints.schoolLanguages,
    columns: [
      textColumn('Code', 'code'),
      textColumn('Name', 'name'),
      {
        id: 'is_default',
        header: 'Default',
        cell: ({ row }) => (row.original.is_default ? 'Yes' : 'No'),
      },
      {
        id: 'is_active',
        header: 'Status',
        cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active'),
      },
    ],
  },
  'hr-discipline': { title: 'Disciplinary Records', endpoint: moduleEndpoints.discipline, columns: disciplineColumns },

  compliance: { title: 'Compliance Policies', endpoint: moduleEndpoints.compliancePolicies, columns: complianceColumns },
  'compliance-incidents': { title: 'Compliance Incidents', endpoint: moduleEndpoints.complianceIncidents, columns: complianceIncidentColumns },
  'compliance-consent': { title: 'Consent Forms', endpoint: moduleEndpoints.consentForms, columns: consentColumns },
  'compliance-audit': { title: 'Audit Trail', endpoint: moduleEndpoints.auditLogs, columns: auditColumns },
  'compliance-login-history': { title: 'Login History', endpoint: moduleEndpoints.auditLoginHistory, columns: defaultColumns(['user_id', 'ip_address', 'created_at']) },

  reports: { title: 'Reports', endpoint: moduleEndpoints.reports, columns: genericColumns },

  'admin-users': { title: 'Users', endpoint: moduleEndpoints.users, columns: userColumns },
  'admin-roles': { title: 'Roles', endpoint: moduleEndpoints.roles, columns: defaultColumns(['name', 'slug']) },

  settings: { title: 'Settings', endpoint: moduleEndpoints.settingsSchool, columns: genericColumns },
  'settings-custom-fields': {
    title: 'Custom Fields',
    description: 'Extra fields on student, teacher, and staff forms.',
    endpoint: moduleEndpoints.customFields,
    columns: [
      textColumn('Name', 'name'),
      textColumn('Entity', 'entity_type'),
      textColumn('Field type', 'field_type'),
      { id: 'is_required', header: 'Required', cell: ({ row }) => (row.original.is_required ? 'Yes' : 'No') },
      { id: 'is_active', header: 'Status', cell: ({ row }) => (row.original.is_active === false ? 'Inactive' : 'Active') },
    ],
  },

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
  'enterprise-warnings': {
    title: 'Early Warnings',
    description: 'Computed risk signals — high dropout risk and unpaid fee balances.',
    endpoint: moduleEndpoints.enterpriseEarlyWarnings,
    columns: [
      textColumn('Student', 'student_name'),
      textColumn('Type', 'type'),
      {
        id: 'severity',
        header: 'Severity',
        cell: ({ row }) => {
          const level = row.original.risk_level
          if (level != null) return String(level)
          const balance = row.original.balance
          if (balance != null) return `Balance: ${balance}`
          return '—'
        },
      },
      {
        id: 'detail',
        header: 'Detail',
        cell: ({ row }) => {
          if (row.original.type === 'unpaid_fees') {
            return row.original.balance != null ? `Owes ${row.original.balance}` : '—'
          }
          const attendance = row.original.attendance_rate
          const score = row.original.average_score_percent
          const risk = row.original.dropout_risk_score
          const parts = [
            attendance != null ? `Attendance ${attendance}%` : null,
            score != null ? `Avg ${score}%` : null,
            risk != null ? `Risk ${risk}` : null,
          ].filter(Boolean)
          return parts.length ? parts.join(' · ') : '—'
        },
      },
    ],
  },
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
