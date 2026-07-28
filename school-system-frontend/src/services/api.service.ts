import type { ListQueryParams } from '@/types/api'
import { api } from '@/lib/api'
import { unwrapOne, unwrapList } from '@/lib/api-response'
import {
  createRecord,
  deleteRecord,
  fetchList,
  fetchOne,
  fetchPaginatedList,
  patchRecord,
  postRecord,
  updateRecord,
} from './dashboard.service'
import { endpoints as e } from './endpoints'
import { forgotPassword, resetPassword } from './auth.service'

export const authApi = {
  forgotPassword,
  resetPassword,
}

function crud(base: string, detail = (id: number | string) => `${base}/${id}`) {
  return {
    list: (params?: ListQueryParams) => fetchList(base, params),
    get: (id: number | string) => fetchOne(detail(id)),
    create: (payload: Record<string, unknown>) => createRecord(base, payload),
    update: (id: number | string, payload: Record<string, unknown>) => updateRecord(detail(id), payload),
    remove: (id: number | string) => deleteRecord(detail(id)),
  }
}

export const schoolApi = {
  show: () => fetchOne(e.school.show),
  update: (payload: Record<string, unknown>) => updateRecord(e.school.show, payload),
  settings: () => fetchOne(e.settings.school),
  updateSettings: (settings: Array<{ group: string; key: string; value: unknown }>) =>
    updateRecord(e.settings.school, { settings }),
  terminology: () => fetchOne(e.settings.terminology),
  updateTerminology: (payload: Record<string, unknown>) => updateRecord(e.settings.terminology, payload),
  customFields: () => fetchList(e.settings.customFields),
  createCustomField: (payload: Record<string, unknown>) => createRecord(e.settings.customFields, payload),
  deleteCustomField: (id: number | string) => deleteRecord(e.settings.customField(id)),
  publicConfig: () => fetchOne(e.settings.config),
}

export const profileApi = {
  show: () => fetchOne(e.profile.show),
  update: (payload: Record<string, unknown>) => updateRecord(e.profile.update, payload),
  changePassword: (payload: Record<string, unknown>) =>
    postRecord(e.auth.changePassword, payload),
}

export interface UploadedFileMeta {
  path: string
  url: string
  name: string
  mime: string | null
  size: number
}

export const uploadsApi = {
  upload: async (file: File, directory?: string) => {
    const form = new FormData()
    form.append('file', file)
    if (directory) form.append('directory', directory)
    const { data } = await api.post(e.uploads, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return unwrapOne<UploadedFileMeta>(data)
  },
}

export const teacherPortalApi = {
  dashboard: () => fetchOne(e.teacherPortal.dashboard),
  classes: () => fetchOne(e.teacherPortal.classes),
  student: (id: number | string) => fetchOne(e.teacherPortal.student(id)),
  submitAttendance: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.attendanceSubmit, payload),
  lockAttendance: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.attendanceLock, payload),
  attendanceReports: (params?: ListQueryParams) =>
    fetchOne(e.teacherPortal.attendanceReports, params),
  freePeriods: () => fetchOne(e.teacherPortal.freePeriods),
  examTimetable: () => fetchOne(e.teacherPortal.examTimetable),
  timetableChangeRequests: () => fetchOne(e.teacherPortal.timetableChangeRequests),
  requestTimetableChange: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.timetableChangeRequests, payload),
  calendar: () => fetchOne(e.teacherPortal.calendar),
  lessonPlans: () => fetchOne(e.teacherPortal.lessonPlans),
  createLessonPlan: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.lessonPlans, payload),
  updateLessonPlan: (id: number | string, payload: Record<string, unknown>) =>
    updateRecord(e.teacherPortal.lessonPlan(id), payload),
  syllabusTopics: (params?: ListQueryParams) =>
    fetchOne(e.teacherPortal.syllabusTopics, params),
  createSyllabusTopic: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.syllabusTopics, payload),
  updateSyllabusTopic: (id: number | string, payload: Record<string, unknown>) =>
    updateRecord(e.teacherPortal.syllabusTopic(id), payload),
  resources: (params?: ListQueryParams) => fetchOne(e.teacherPortal.resources, params),
  createResource: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.resources, payload),
  submissions: (assignmentId: number | string) =>
    fetchOne(e.teacherPortal.submissions(assignmentId)),
  storeSubmission: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.storeSubmission, payload),
  gradeSubmission: (id: number | string, payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.gradeSubmission(id), payload),
  onlineLessons: () => fetchOne(e.teacherPortal.onlineLessons),
  createOnlineLesson: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.onlineLessons, payload),
  reportCards: () => fetchOne(e.teacherPortal.reportCards),
  saveReportCard: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.reportCards, payload),
  behaviour: (params?: ListQueryParams) => fetchOne(e.teacherPortal.behaviour, params),
  recordBehaviour: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.behaviour, payload),
  interventions: () => fetchOne(e.teacherPortal.interventions),
  createIntervention: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.interventions, payload),
  participation: () => fetchOne(e.teacherPortal.participation),
  recordParticipation: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.participation, payload),
  department: () => fetchOne(e.teacherPortal.department),
  leave: () => fetchOne(e.teacherPortal.leave),
  applyLeave: (payload: Record<string, unknown>) => postRecord(e.teacherPortal.leave, payload),
  substitutions: () => fetchOne(e.teacherPortal.substitutions),
  acceptSubstitution: (id: number | string) =>
    postRecord(e.teacherPortal.acceptSubstitution(id), {}),
  notifications: () => fetchOne(e.teacherPortal.notifications),
  markNotificationRead: (id: number | string) =>
    postRecord(e.teacherPortal.markNotificationRead(id), {}),
  markAllNotificationsRead: () => postRecord(e.teacherPortal.markAllNotificationsRead, {}),
  aiGenerate: (payload: Record<string, unknown>) =>
    postRecord(e.teacherPortal.aiGenerate, payload),
  exportUrl: (params: Record<string, string | number>) => {
    const qs = new URLSearchParams(
      Object.entries(params).map(([k, v]) => [k, String(v)]),
    ).toString()
    return `${e.teacherPortal.export}?${qs}`
  },
}

export const studentPortalApi = {
  dashboard: () => fetchOne(e.studentPortal.dashboard),
  me: () => fetchOne(e.studentPortal.me),
  attendance: () => fetchOne(e.studentPortal.attendance),
  grades: () => fetchOne(e.studentPortal.grades),
  fees: () => fetchOne(e.studentPortal.fees),
  timetable: () => fetchOne(e.studentPortal.timetable),
  assignments: () => fetchOne(e.studentPortal.assignments),
  assignment: (id: number | string) => fetchOne(e.studentPortal.assignment(id)),
  submitAssignment: async (id: number | string, payload: { content?: string; file?: File | null }) => {
    const form = new FormData()
    if (payload.content) form.append('content', payload.content)
    if (payload.file) form.append('file', payload.file)
    const { data } = await api.post(e.studentPortal.submitAssignment(id), form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return (data as { data?: unknown }).data ?? data
  },
  announcements: () => fetchOne(e.studentPortal.announcements),
  cbtAvailable: () => fetchOne(e.studentPortal.cbtAvailable),
  cbtStart: (payload: { subject_id: number; count?: number; exam_id?: number }) =>
    postRecord(e.studentPortal.cbtStart, payload),
  cbtSession: (id: number | string) => fetchOne(e.studentPortal.cbtSession(id)),
  cbtSubmit: (id: number | string, answers: Array<{ question_id: number; answer: string }>) =>
    postRecord(e.studentPortal.cbtSubmit(id), { answers }),
  cbtAntiCheat: (id: number | string, event_type: string, metadata?: Record<string, unknown>) =>
    postRecord(e.studentPortal.cbtAntiCheat(id), { event_type, metadata }),
}

export const studentsApi = {
  ...crud(e.students.list, e.students.detail),
  promote: (payload: Record<string, unknown>) => postRecord(e.students.promote, payload),
  bulkInvoices: (payload: Record<string, unknown>) => postRecord(e.students.bulkInvoices, payload),
  bulkStatus: (payload: Record<string, unknown>) => postRecord(e.students.bulkStatus, payload),
  bulkPromote: (payload: Record<string, unknown>) => postRecord(e.students.bulkPromote, payload),
  performance: (id: number | string) => fetchOne(e.students.performance(id)),
  lifecycle: (id: number | string) => fetchOne(e.students.lifecycle(id)),
  place: (id: number | string, payload: Record<string, unknown>) =>
    postRecord(e.students.placements(id), payload),
  lifecycleTransition: (id: number | string, payload: Record<string, unknown>) =>
    postRecord(e.students.lifecycleTransition(id), payload),
  lifecyclePromote: (id: number | string, payload: Record<string, unknown>) =>
    postRecord(e.students.lifecyclePromote(id), payload),
  downloadTransferCertificate: async (id: number | string) => {
    const { data } = await api.get(e.students.transferCertificate(id), { responseType: 'blob' })
    return data as Blob
  },
  downloadTranscript: async (id: number | string) => {
    const { data } = await api.get(e.students.transcript(id), { responseType: 'blob' })
    return data as Blob
  },
  invoices: (id: number | string) => fetchList(e.students.invoices(id)),
  createInvoice: (id: number | string, payload: Record<string, unknown>) =>
    createRecord(e.students.createInvoice(id), payload),
  documents: (id: number | string) => fetchList(e.students.documents(id)),
  uploadDocuments: async (id: number | string, files: File[], type?: string) => {
    const form = new FormData()
    files.forEach((file) => form.append('documents[]', file))
    if (type) form.append('type', type)
    const { data } = await api.post(e.students.documents(id), form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return unwrapList(data)
  },
  deleteDocument: (id: number | string, documentId: number | string) =>
    deleteRecord(`${e.students.documents(id)}/${documentId}`),
  exams: (id: number | string) => fetchList(e.students.exams(id)),
  downloadResults: async (id: number | string, format: 'html' | 'csv' = 'html') => {
    const { data } = await api.get(e.students.resultsDownload(id), {
      params: { format },
      responseType: 'blob',
    })
    return data as Blob
  },
  printIdCard: (id: number | string) => fetchOne(e.students.idCardPrint(id)),
  uploadPhoto: async (id: number | string, file: File) => {
    const form = new FormData()
    form.append('file', file)
    const { data } = await api.post(e.students.photo(id), form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return unwrapOne<{ photo_url: string; student: Record<string, unknown> }>(data)
  },
  guardians: (id: number | string) => fetchList(e.students.guardians(id)),
  categories: crud(e.studentCategories.list, e.studentCategories.detail),
}

export const academicStructureApi = {
  streams: {
    list: (params?: ListQueryParams) => fetchList(e.streams.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.streams.list, payload),
    update: (id: number | string, payload: Record<string, unknown>) =>
      updateRecord(e.streams.detail(id), payload),
  },
  houses: {
    list: (params?: ListQueryParams) => fetchList(e.houses.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.houses.list, payload),
    update: (id: number | string, payload: Record<string, unknown>) =>
      updateRecord(e.houses.detail(id), payload),
  },
  subjectPackages: {
    list: (params?: ListQueryParams) => fetchList(e.subjectPackages.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.subjectPackages.store, payload),
    update: (id: number | string, payload: Record<string, unknown>) =>
      updateRecord(e.subjectPackages.detail(id), payload),
    remove: (id: number | string) => deleteRecord(e.subjectPackages.detail(id)),
  },
}

export const teachersApi = {
  ...crud(e.teachers.list, e.teachers.detail),
  updateStatus: (id: number | string, payload: Record<string, unknown>) =>
    patchRecord(e.teachers.status(id), payload),
}

export const guardiansApi = {
  ...crud(e.guardians.list, e.guardians.detail),
  students: (id: number | string) => fetchList(e.guardians.students(id)),
  linkStudent: (id: number | string, payload: Record<string, unknown>) =>
    postRecord(e.guardians.linkStudent(id), payload),
}

export const enrollmentApi = {
  ...crud(e.enrollment.list, e.enrollment.detail),
  approve: (id: number | string, payload?: Record<string, unknown>) =>
    updateRecord(e.enrollment.approve(id), payload ?? {}),
  reject: (id: number | string, payload?: Record<string, unknown>) =>
    updateRecord(e.enrollment.reject(id), payload ?? { notes: 'Rejected' }),
}

export const usersApi = {
  ...crud(e.users.list, e.users.detail),
  activate: (id: number | string) => patchRecord(e.users.activate(id)),
  deactivate: (id: number | string) => patchRecord(e.users.deactivate(id)),
  resetPassword: (id: number | string, payload: Record<string, unknown>) =>
    postRecord(e.users.resetPassword(id), payload),
  assignRole: (id: number | string, payload: Record<string, unknown>) =>
    patchRecord(e.users.assignRole(id), payload),
  roles: () => fetchList(e.roles.list),
  permissions: () => fetchList(e.permissions.list),
}

export const rolesApi = {
  ...crud(e.roles.list, e.roles.detail),
  permissions: () => fetchList(e.permissions.list),
}

export const academicsApi = {
  classes: crud(e.classes.list, e.classes.detail),
  subjects: crud(e.subjects.list, e.subjects.detail),
  departments: crud(e.departments.list, e.departments.detail),
  gradeLevels: crud(e.gradeLevels.list, e.gradeLevels.detail),
  gradingScales: {
    ...crud(e.gradingScales.list, e.gradingScales.detail),
    getGradeForScore: (payload: Record<string, unknown>) => postRecord(e.gradingScales.getGrade, payload),
  },
  rooms: crud(e.rooms.list, e.rooms.detail),
  terms: {
    ...crud(e.terms.list, e.terms.detail),
    current: () => fetchOne(e.terms.current),
  },
  assignments: crud(e.assignments.list, e.assignments.detail),
  tests: {
    ...crud(e.tests.list, e.tests.detail),
    recordResults: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.tests.recordResults(id), payload),
  },
  teacherAssignments: crud(e.teacherAssignments.list, e.teacherAssignments.detail),
  grades: {
    byClass: (classId: number | string) => fetchList(e.grades.byClass(classId)),
    byStudent: (studentId: number | string) => fetchList(e.grades.byStudent(studentId)),
    store: (payload: Record<string, unknown>) => createRecord(e.grades.store, payload),
    bulkUpload: (payload: Record<string, unknown>) => postRecord(e.grades.bulk, payload),
    classPerformance: (classId: number | string) => fetchOne(e.grades.classPerformance(classId)),
  },
  exams: {
    ...crud(e.exams.list, e.exams.detail),
    analytics: () => fetchOne(e.exams.analytics),
    approveResults: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.exams.approveResults(id), payload ?? {}),
    publish: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.exams.publish(id), payload ?? {}),
    recordResults: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.exams.recordResults(id), payload),
  },
  examSchedules: crud(e.examSchedules.list, e.examSchedules.detail),
  certificateTemplates: {
    ...crud(e.certificateTemplates.list, e.certificateTemplates.detail),
    issue: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.certificateTemplates.issue(id), payload),
  },
  schoolCertificates: crud(e.schoolCertificates.list, e.schoolCertificates.detail),
  attendance: {
    list: (params?: ListQueryParams) => fetchList(e.attendance.list, params),
    record: (payload: Record<string, unknown>) => createRecord(e.attendance.list, payload),
    todaySummary: () => fetchOne(e.attendance.todaySummary),
    studentSummary: (id: number | string) => fetchOne(e.attendance.studentSummary(id)),
    classReport: (id: number | string) => fetchOne(e.attendance.classReport(id)),
  },
  timetable: {
    ...crud(e.timetable.list, e.timetable.detail),
    generate: (payload: Record<string, unknown>) => postRecord(e.timetable.generate, payload),
    generateBulk: (payload: Record<string, unknown>) => postRecord(e.timetable.generateBulk, payload),
  },
  holidayPrograms: {
    ...crud(e.holidayPrograms.list, e.holidayPrograms.detail),
    enrollments: (id: number | string) => fetchList(e.holidayPrograms.enrollments(id)),
    enroll: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.holidayPrograms.enroll(id), payload),
    attendance: (id: number | string, params?: ListQueryParams) =>
      fetchList(e.holidayPrograms.attendance(id), params),
    recordAttendance: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.holidayPrograms.attendance(id), payload),
  },
  schoolTrips: {
    ...crud(e.schoolTrips.list, e.schoolTrips.detail),
    enrollments: (id: number | string) => fetchList(e.schoolTrips.enrollments(id)),
    enroll: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.schoolTrips.enroll(id), payload),
  },
}

export const financeApi = {
  summary: () => fetchOne(e.finance.summary),
  outstandingBalances: (params?: ListQueryParams) => fetchPaginatedList(e.finance.outstandingBalances, params),
  aging: () => fetchOne<Record<string, unknown>>(e.finance.aging),
  reconciliation: async (params?: ListQueryParams) => {
    const { data } = await api.get(e.finance.reconciliation, { params })
    return unwrapOne<Record<string, unknown>>(data)
  },
  cashFlow: async (params?: ListQueryParams) => {
    const { data } = await api.get(e.finance.cashFlow, { params })
    return unwrapOne<Record<string, unknown>>(data)
  },
  periodReport: (period: string) => fetchOne(e.finance.periodReport(period)),
  payments: {
    list: (params?: ListQueryParams) => fetchList(e.payments.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.payments.list, payload),
    remove: (id: number | string) => deleteRecord(e.payments.detail(id)),
    receipt: (id: number | string) => fetchOne(e.payments.receipt(id)),
    reverse: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.payments.reverse(id), payload ?? {}),
  },
  transactions: {
    list: (params?: ListQueryParams) => fetchPaginatedList(e.transactions.list, params),
    listAll: (params?: ListQueryParams) => fetchList(e.transactions.list, { ...params, all: true }),
    summary: (params?: ListQueryParams) => fetchOne(e.transactions.summary, params),
  },
  invoices: {
    list: (params?: ListQueryParams) => fetchList(e.invoices.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.invoices.list, payload),
    update: (id: number | string, payload: Record<string, unknown>) =>
      updateRecord(e.invoices.detail(id), payload),
    print: (id: number | string) => fetchOne(e.invoices.print(id)),
  },
  feeStructures: crud(e.feeStructures.list, e.feeStructures.detail),
  feeCategories: crud(e.feeCategories.list, e.feeCategories.detail),
  feeGroups: crud(e.feeGroups.list, e.feeGroups.detail),
  feeDiscounts: crud(e.feeDiscounts.list, e.feeDiscounts.detail),
  incomeHeads: crud(e.incomeHeads.list, e.incomeHeads.detail),
  expenseHeads: crud(e.expenseHeads.list, e.expenseHeads.detail),
  schoolCurrencies: crud(e.schoolCurrencies.list, e.schoolCurrencies.detail),
  schoolLanguages: crud(e.schoolLanguages.list, e.schoolLanguages.detail),
  payroll: {
    list: (params?: ListQueryParams) => fetchList(e.payroll.list, params),
    teachers: () => fetchList(e.payroll.teachers),
    summary: (params?: ListQueryParams) => fetchOne(e.payroll.summary, params),
    trends: (params?: ListQueryParams) => fetchList(e.payroll.trends, params),
    departmentSummary: (params?: ListQueryParams) =>
      fetchOne(e.payroll.departmentSummary, params),
    teacherHistory: (id: number | string) => fetchList(e.payroll.teacherHistory(id)),
    payslip: (id: number | string) => fetchOne(e.payroll.payslip(id)),
    generate: (payload: Record<string, unknown>) => postRecord(e.payroll.generate, payload),
    update: (id: number | string, payload: Record<string, unknown>) =>
      updateRecord(e.payroll.detail(id), payload),
    process: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.payroll.process(id), payload ?? {}),
  },
}

export const operationsApi = {
  inventory: {
    items: crud(e.inventory.items, e.inventory.item),
    restock: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.inventory.restock(id), payload),
    sales: {
      list: (params?: ListQueryParams) => fetchList(e.inventory.sales, params),
      create: (payload: Record<string, unknown>) => createRecord(e.inventory.sales, payload),
    },
  },
  procurement: {
    requisitions: {
      list: (params?: ListQueryParams) => fetchList(e.procurement.requisitions, params),
      create: (payload: Record<string, unknown>) => createRecord(e.procurement.requisitions, payload),
      submit: (id: number | string) => postRecord(e.procurement.submit(id)),
      disburse: (id: number | string, payload: Record<string, unknown>) =>
        postRecord(e.procurement.disburse(id), payload),
    },
    vendors: {
      list: (params?: ListQueryParams) => fetchList(e.procurement.vendors, params),
      create: (payload: Record<string, unknown>) => createRecord(e.procurement.vendors, payload),
    },
    receiveGoods: (payload: Record<string, unknown>) => postRecord(e.procurement.goodsReceipts, payload),
  },
  assets: {
    list: (params?: ListQueryParams) => fetchList(e.assets.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.assets.list, payload),
    logMaintenance: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.assets.maintenance(id), payload),
    dispose: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.assets.dispose(id), payload ?? {}),
  },
  transport: {
    vehicles: {
      list: (params?: ListQueryParams) => fetchList(e.transport.vehicles, params),
      create: (payload: Record<string, unknown>) => createRecord(e.transport.vehicles, payload),
    },
    drivers: {
      list: (params?: ListQueryParams) => fetchList(e.transport.drivers, params),
      create: (payload: Record<string, unknown>) => createRecord(e.transport.drivers, payload),
    },
    routes: {
      list: (params?: ListQueryParams) => fetchList(e.transport.routes, params),
      create: (payload: Record<string, unknown>) => createRecord(e.transport.routes, payload),
    },
    allocateStudent: (payload: Record<string, unknown>) => postRecord(e.transport.allocations, payload),
  },
  hostels: {
    list: (params?: ListQueryParams) => fetchList(e.hostels.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.hostels.list, payload),
    storeRoom: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.hostels.rooms(id), payload),
    allocate: (payload: Record<string, unknown>) => postRecord(e.hostels.allocations, payload),
  },
  library: {
    books: {
      list: (params?: ListQueryParams) => fetchList(e.library.books, params),
      create: (payload: Record<string, unknown>) => createRecord(e.library.books, payload),
    },
    members: crud(e.libraryMembers.list, e.libraryMembers.detail),
    borrow: (payload: Record<string, unknown>) => postRecord(e.library.borrow, payload),
    returnBook: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.library.return(id), payload ?? {}),
  },
  visitors: {
    list: (params?: ListQueryParams) => fetchList(e.visitors.list, params),
    checkIn: (payload: Record<string, unknown>) => postRecord(e.visitors.checkIn, payload),
    checkOut: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.visitors.checkOut(id), payload ?? {}),
  },
  health: {
    visits: {
      list: (params?: ListQueryParams) => fetchList(e.health.visits, params),
      create: (payload: Record<string, unknown>) => createRecord(e.health.visits, payload),
    },
    profile: (studentId: number | string) => fetchOne(e.health.profile(studentId)),
    updateProfile: (studentId: number | string, payload: Record<string, unknown>) =>
      updateRecord(e.health.profile(studentId), payload),
  },
  events: crud(e.events.list),
}

export const hrApi = {
  leaveRequests: {
    list: (params?: ListQueryParams) => fetchPaginatedList(e.leaveRequests.list, params),
    listAll: (params?: ListQueryParams) => fetchList(e.leaveRequests.list, { ...params, all: true }),
    create: (payload: Record<string, unknown>) => createRecord(e.leaveRequests.list, payload),
    approve: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.leaveRequests.approve(id), payload ?? {}),
    reject: (id: number | string, payload?: Record<string, unknown>) =>
      postRecord(e.leaveRequests.reject(id), payload ?? {}),
  },
  leaveTypes: crud(e.leaveTypes.list, e.leaveTypes.detail),
  designations: crud(e.designations.list, e.designations.detail),
  employees: crud(e.employees.list, e.employees.detail),
  staffAttendance: {
    list: (params?: ListQueryParams) => fetchList(e.staffAttendance.list, params),
    roster: (params?: ListQueryParams) => fetchOne(e.staffAttendance.roster, params),
    summary: (params?: ListQueryParams) => fetchOne(e.staffAttendance.summary, params),
    store: (payload: Record<string, unknown>) => createRecord(e.staffAttendance.store, payload),
  },
  discipline: {
    list: (params?: ListQueryParams) => fetchList(e.discipline.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.discipline.list, payload),
    get: (id: number | string) => fetchOne(e.discipline.detail(id)),
  },
}

export const commsApi = {
  announcements: crud(e.announcements.list, e.announcements.detail),
  parents: (params?: ListQueryParams) => fetchList(e.communications.parents, params),
  threads: {
    list: (params?: ListQueryParams) => fetchList(e.communications.threads, params),
    get: (id: number | string) => fetchOne(e.communications.thread(id)),
    create: (payload: Record<string, unknown>) =>
      postRecord(e.communications.threads, payload),
    reply: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.communications.reply(id), payload),
  },
}

export interface AuditActor {
  id: number | null
  name: string
  email: string | null
  role: string | null
}

export interface AuditChange {
  field: string
  label: string
  from: unknown
  to: unknown
}

export interface AuditLogRow {
  id: number
  module: string
  module_label: string
  action: string
  action_label: string
  description: string | null
  summary: string
  actor: AuditActor
  target: {
    type: string | null
    type_label: string
    id: number | null
    name?: string | null
    label?: string | null
  }
  changes: AuditChange[]
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  metadata: Record<string, unknown> | null
  context: {
    ip_address: string | null
    device_type: string | null
    platform: string | null
    location: string | null
    request_method: string | null
    request_path: string | null
  }
  created_at: string
}

export interface LoginHistoryRow {
  id: number
  event: string
  event_label: string
  summary: string
  email: string | null
  actor: AuditActor
  failure_reason: string | null
  token_name: string | null
  context: {
    ip_address: string | null
    device_type: string | null
    platform: string | null
    location: string | null
  }
  created_at: string
}

export const complianceApi = {
  policies: {
    list: (params?: ListQueryParams) => fetchList(e.compliance.policies, params),
    create: (payload: Record<string, unknown>) => createRecord(e.compliance.policies, payload),
  },
  incidents: {
    list: (params?: ListQueryParams) => fetchList(e.compliance.incidents, params),
    create: (payload: Record<string, unknown>) => createRecord(e.compliance.incidents, payload),
  },
  consentForms: {
    list: (params?: ListQueryParams) => fetchList(e.consentForms.list, params),
    create: (payload: Record<string, unknown>) => createRecord(e.consentForms.list, payload),
  },
  auditLogs: {
    list: (params?: ListQueryParams) => fetchList<AuditLogRow>(e.auditLogs.list, params),
    listPaginated: (params?: ListQueryParams) =>
      fetchPaginatedList<AuditLogRow>(e.auditLogs.list, params),
    loginHistory: (params?: ListQueryParams) =>
      fetchList<LoginHistoryRow>(e.auditLogs.loginHistory, params),
    loginHistoryPaginated: (params?: ListQueryParams) =>
      fetchPaginatedList<LoginHistoryRow>(e.auditLogs.loginHistory, params),
    get: (id: number | string) => fetchOne<AuditLogRow>(e.auditLogs.detail(id)),
  },
}

export const workflowsApi = {
  pending: (params?: ListQueryParams) => fetchList(e.workflows.pending, params),
  history: (params?: ListQueryParams) => fetchList(e.workflows.history, params),
  get: (id: number | string) => fetchOne(e.workflows.detail(id)),
  approve: (id: number | string, payload?: Record<string, unknown>) =>
    postRecord(e.workflows.approve(id), payload ?? {}),
  reject: (id: number | string, payload?: Record<string, unknown>) =>
    postRecord(e.workflows.reject(id), payload ?? {}),
}

export interface ReportExportPayload {
  type: 'academic-performance' | 'attendance' | 'financial'
  format?: 'csv' | 'json' | null
  term?: string | null
  year?: number | null
  subject?: string | null
  class_id?: number | null
  from?: string | null
  to?: string | null
  currency?: string | null
}

export const reportsApi = {
  templates: (params?: ListQueryParams) => fetchList(e.reports.templates, params),
  export: async (payload: ReportExportPayload) => {
    const { data } = await api.get(e.reports.export, { params: payload })
    return unwrapOne<Record<string, unknown>>(data)
  },
  academicPerformance: (params?: ListQueryParams) => fetchOne(e.reports.academicPerformance, params),
  attendance: (params?: ListQueryParams) => fetchOne(e.reports.attendance, params),
  financial: (params?: ListQueryParams) => fetchOne(e.reports.financial, params),
  classReport: (id: number | string) => fetchOne(e.reports.classReport(id)),
  generate: (type: string) => fetchOne(e.reports.generate(type)),
  store: (payload: Record<string, unknown>) => createRecord(e.reports.store, payload),
}

export const parentPortalApi = {
  dashboard: () => fetchOne(e.parentPortal.dashboard),
  children: () => fetchList(e.parentPortal.children),
  results: (studentId: number | string) =>
    fetchOne<{
      student?: Record<string, unknown>
      exam_results?: Record<string, unknown>[]
      report_card_grades?: Record<string, unknown>[]
    }>(e.parentPortal.results(studentId)),
  attendance: (studentId: number | string) =>
    fetchOne<{
      student?: Record<string, unknown>
      summary?: Record<string, number>
      records?: Record<string, unknown>[]
    }>(e.parentPortal.attendance(studentId)),
  fees: (studentId: number | string) => fetchOne(e.parentPortal.fees(studentId)),
  discipline: (studentId: number | string) =>
    fetchOne<{
      student?: Record<string, unknown>
      records?: Record<string, unknown>[]
    }>(e.parentPortal.discipline(studentId)),
  progress: (studentId: number | string) => fetchOne(e.parentPortal.progress(studentId)),
  announcements: () => fetchList(e.parentPortal.announcements),
  notifications: (params?: ListQueryParams) => fetchList(e.parentPortal.notifications, params),
  markNotificationRead: (id: number | string) => postRecord(e.parentPortal.markNotificationRead(id)),
  markAllNotificationsRead: () => postRecord(e.parentPortal.markAllNotificationsRead),
  threads: () => fetchList(e.parentPortal.threads),
  createThread: (payload: Record<string, unknown>) => createRecord(e.parentPortal.threads, payload),
  threadMessages: (threadId: number | string) =>
    fetchOne<{ thread?: Record<string, unknown>; messages?: Record<string, unknown>[] }>(
      e.parentPortal.threadMessages(threadId),
    ),
  sendMessage: (threadId: number | string, payload: Record<string, unknown>) =>
    postRecord(e.parentPortal.threadMessages(threadId), payload),
  consentForms: () => fetchList(e.parentPortal.consentForms),
  respondConsent: (id: number | string, payload: Record<string, unknown>) =>
    postRecord(e.parentPortal.respondConsent(id), payload),
  storeItems: (params?: ListQueryParams) => fetchList(e.parentPortal.storeItems, params),
  storeBuy: (payload: Record<string, unknown>) => createRecord(e.parentPortal.storeBuy, payload),
  trips: () => fetchList(e.parentPortal.trips),
  enrollTrip: (id: number | string, payload: Record<string, unknown>) =>
    postRecord(e.parentPortal.enrollTrip(id), payload),
}

export const enterpriseApi = {
  commandCenter: () => fetchOne(e.enterprise.commandCenter),
  academic: {
    calendar: (params?: ListQueryParams) => fetchList(e.enterprise.academic.calendar, params),
    storeCalendarEntry: (payload: Record<string, unknown>) =>
      createRecord(e.enterprise.academic.calendar, payload),
    curriculum: () => fetchList(e.enterprise.academic.curriculum),
    assessmentCategories: () => fetchList(e.enterprise.academic.assessmentCategories),
    promotionRules: () => fetchList(e.enterprise.academic.promotionRules),
    gpaRanking: () => fetchList(e.enterprise.academic.gpaRanking),
  },
  finance: {
    accounts: () => fetchList(e.enterprise.finance.accounts),
    seedAccounts: () => postRecord(e.enterprise.finance.seedAccounts),
    postJournal: (payload: Record<string, unknown>) => postRecord(e.enterprise.finance.journals, payload),
    exchangeRates: () => fetchList(e.enterprise.finance.exchangeRates),
    instalmentPlans: () => fetchList(e.enterprise.finance.instalmentPlans),
    bankStatements: () => fetchList(e.enterprise.finance.bankStatements),
    profitLoss: () => fetchOne(e.enterprise.finance.profitLoss),
    balanceSheet: () => fetchOne(e.enterprise.finance.balanceSheet),
    cashflowForecast: () => fetchOne(e.enterprise.finance.cashflowForecast),
    revenueRules: () => fetchList(e.enterprise.finance.revenueRules),
  },
  exams: {
    questionBank: () => fetchList(e.enterprise.exams.questionBank),
    generatePaper: (payload: Record<string, unknown>) => postRecord(e.enterprise.exams.generatePaper, payload),
    startCbt: (payload: Record<string, unknown>) => postRecord(e.enterprise.exams.startCbt, payload),
    submitCbt: (id: number | string, payload: Record<string, unknown>) =>
      postRecord(e.enterprise.exams.submitCbt(id), payload),
    remarkRequests: () => fetchList(e.enterprise.exams.remarkRequests),
    delegations: () => fetchList(e.enterprise.exams.delegations),
  },
  hr: {
    performanceReviews: () => fetchList(e.enterprise.hr.performanceReviews),
    contracts: () => fetchList(e.enterprise.hr.contracts),
    certifications: () => fetchList(e.enterprise.hr.certifications),
  },
  earlyWarnings: () => fetchList(e.enterprise.intelligence.earlyWarnings),
  alumni: () => fetchList(e.enterprise.intelligence.alumni),
  campaigns: () => fetchList(e.enterprise.intelligence.campaigns),
}

export const assistantApi = {
  status: () => fetchOne<{
    configured: boolean
    provider: string
    mode: string
    message: string
  }>(e.assistant.status),
  chat: (payload: Record<string, unknown>) => postRecord(e.assistant.chat, payload),
  conversations: (params?: ListQueryParams) => fetchList(e.assistant.conversations, params),
  getConversation: (id: number | string) => fetchOne(e.assistant.conversation(id)),
}

export interface PlatformLicenseSummary {
  schools: { total: number; licensed: number; unlicensed: number; expired: number }
  keys: { total: number; unused: number; active: number; expired: number; revoked: number }
  revenue?: {
    currency: string
    mtd: number
    ytd: number
    recognized_total: number
    by_plan: Record<string, number>
  }
}

export interface SchoolLicenseRow {
  id: number
  name: string
  code: string
  email?: string | null
  phone?: string | null
  status: string
  license_status: string
  license_plan?: string | null
  license_expires_at?: string | null
  users_count: number
  license_state: { status: string; message: string; days_remaining?: number | null }
  active_key?: {
    id: number
    key_prefix: string
    plan_type: string
    status: string
    activated_at?: string | null
    expires_at?: string | null
  } | null
}

export interface LicenseKeyRow {
  id: number
  key_prefix: string
  plan_type: string
  duration_months?: number | null
  status: string
  school?: { id: number; name: string; code: string } | null
  customer_name?: string | null
  customer_email?: string | null
  activated_at?: string | null
  expires_at?: string | null
  created_at?: string | null
  created_by?: { id: number; name: string; email: string } | null
}

export const platformApi = {
  licenseOverview: async (params?: ListQueryParams) => {
    const { data } = await api.get(e.license.admin.list, { params })
    return unwrapOne<{
      keys?: LicenseKeyRow[]
      summary?: PlatformLicenseSummary
    }>(data)
  },
  schoolsOverview: async (params?: ListQueryParams) => {
    const { data } = await api.get(e.license.admin.schools, { params })
    return unwrapOne<{
      schools?: SchoolLicenseRow[]
      summary?: PlatformLicenseSummary
    }>(data)
  },
  provisionSchool: (payload: Record<string, unknown>) =>
    createRecord<{
      school: Record<string, unknown>
      admin: { id: number; name: string; email: string; role: string }
      license_key?: string | null
      license_status?: string | null
      login_hint?: string
    }>(e.license.admin.createSchool, payload),
  getSchool: (id: number | string) =>
    fetchOne<{
      school: SchoolLicenseRow & Record<string, unknown>
      domains: Array<Record<string, unknown>>
      usage?: Record<string, unknown>
    }>(e.license.admin.school(id)),
  updateSchoolStatus: (id: number | string, status: 'active' | 'suspended') =>
    patchRecord<{ school: SchoolLicenseRow }>(e.license.admin.schoolStatus(id), { status }),
  deleteSchool: async (id: number | string, payload?: { backup?: boolean }) => {
    const { data } = await api.delete(e.license.admin.school(id), { data: payload })
    return unwrapOne(data)
  },
  schoolUsage: (id: number | string) => fetchOne<{ usage: Record<string, number | string | null> }>(e.license.admin.schoolUsage(id)),
  listSchoolBackups: (id: number | string) =>
    fetchOne<{ backups: Array<Record<string, unknown>> }>(e.license.admin.schoolBackups(id)),
  createSchoolBackup: (id: number | string) =>
    createRecord<{ backup: Record<string, unknown> }>(e.license.admin.schoolBackups(id), {}),
  restoreSchoolBackup: (schoolId: number | string, backupId: number | string) =>
    postRecord<{ backup: Record<string, unknown> }>(e.license.admin.restoreBackup(schoolId, backupId)),
  listSchoolDomains: (id: number | string) =>
    fetchOne<{ domains: Array<Record<string, unknown>> }>(e.license.admin.schoolDomains(id)),
  addSchoolDomain: (id: number | string, payload: { domain: string; is_primary?: boolean }) =>
    createRecord<{ domain: Record<string, unknown> }>(e.license.admin.schoolDomains(id), payload),
  verifySchoolDomain: (schoolId: number | string, domainId: number | string, force = false) =>
    postRecord<{ domain: Record<string, unknown> }>(
      `${e.license.admin.verifyDomain(schoolId, domainId)}${force ? '?force=1' : ''}`,
    ),
  deleteSchoolDomain: (schoolId: number | string, domainId: number | string) =>
    deleteRecord(e.license.admin.schoolDomain(schoolId, domainId)),
  licenses: async (params?: ListQueryParams) => {
    const payload = await platformApi.licenseOverview(params)
    return Array.isArray(payload?.keys) ? payload.keys : []
  },
  generateLicense: (payload: Record<string, unknown>) =>
    createRecord<{ license_key: string; record: Record<string, unknown> }>(e.license.admin.list, payload),
  revokeLicense: (id: number | string) => postRecord(e.license.admin.revoke(id)),
  systemHealth: () => fetchOne(e.platform.systemHealth),
  operationsLive: () => fetchOne(e.platform.operationsLive),
  resolveAlert: (id: number | string) => postRecord(e.platform.resolveAlert(id)),
  apiClients: () => fetchList(e.platform.apiClients),
  policyRules: () => fetchList(e.platform.policyRules),
  workflowDefinitions: () => fetchList(e.platform.workflowDefinitions),
  branchTree: () => fetchOne(e.platform.branchTree),
  retentionPolicies: () => fetchList(e.platform.retentionPolicies),
  maskingRules: () => fetchList(e.platform.maskingRules),
  documents: (params?: ListQueryParams) => fetchList(e.platform.documents, params),
  createDocument: (payload: Record<string, unknown>) => createRecord(e.platform.documents, payload),
  signDocument: (id: number | string, payload?: Record<string, unknown>) =>
    postRecord(e.platform.signDocument(id), payload),
  certificates: () => fetchList(e.platform.certificates),
  vault: () => fetchList(e.platform.vault),
  scholarships: () => fetchList(e.platform.scholarships),
  paymentGateways: () => fetchList(e.platform.paymentGateways),
  savePaymentGateway: (payload: Record<string, unknown>) =>
    createRecord(e.platform.paymentGateways, payload),
  initiatePayment: (payload: Record<string, unknown>) =>
    createRecord(e.platform.initiatePayment, payload),
  paymentStatus: (reference: string) => fetchOne(e.platform.paymentStatus(reference)),
  refunds: () => fetchList(e.platform.refunds),
  behaviorPoints: () => fetchList(e.platform.behaviorPoints),
  storeBehaviorPoint: (payload: Record<string, unknown>) =>
    createRecord(e.platform.behaviorPoints, payload),
  interventions: () => fetchList(e.platform.interventions),
  storeIntervention: (payload: Record<string, unknown>) =>
    createRecord(e.platform.interventions, payload),
  staffTasks: (params?: ListQueryParams) => fetchList(e.platform.staffTasks, params),
  createStaffTask: (payload: Record<string, unknown>) => createRecord(e.platform.staffTasks, payload),
  updateStaffTask: (id: number | string, payload: Record<string, unknown>) =>
    updateRecord(e.platform.staffTask(id), payload),
  staffFeed: () => fetchList(e.platform.staffFeed),
  predictiveAnalytics: () => fetchOne(e.platform.predictiveAnalytics),
  auditIntegrity: () => fetchOne(e.platform.auditIntegrity),
  communications: {
    send: (payload: Record<string, unknown>) => postRecord(e.platform.communications.send, payload),
    tracking: (params?: ListQueryParams) => fetchList(e.platform.communications.tracking, params),
    markRead: (id: number | string) => postRecord(e.platform.communications.markRead(id)),
  },
}

export { endpoints } from './endpoints'
