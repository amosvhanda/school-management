import { api } from '@/lib/api'
import { unwrapList, unwrapOne, unwrapPaginator } from '@/lib/api-response'
import type { ListQueryParams, Paginator } from '@/types/api'
import { endpoints as e } from './endpoints'
import type {
  ActivityPoint,
  AttendanceSummary,
  CommandCenterData,
  DashboardKpis,
  LmsDashboardWidgets,
  MonthlyStat,
  PayrollSummary,
  RecentActivityItem,
  RoleDashboardPreview,
  SchoolDashboardWidgets,
} from '@/types/dashboard'

const defaultAttendance: AttendanceSummary = {
  present: 0,
  absent: 0,
  late: 0,
  excused: 0,
  half_day: 0,
  total: 0,
  date: new Date().toISOString().slice(0, 10),
}

const defaultPayroll: PayrollSummary = {
  total_payroll: 0,
  total_paid: 0,
  total_pending: 0,
  total_partial: 0,
}

export function defaultKpis(): DashboardKpis {
  return {
    totalStudents: 0,
    activeStudents: 0,
    totalTeachers: 0,
    totalClasses: 0,
    totalParents: 0,
    totalUsers: 0,
    outstandingFees: 0,
    paymentsToday: 0,
    totalRevenue: 0,
    attendanceSummary: { ...defaultAttendance },
    payrollSummary: { ...defaultPayroll },
    totalActivity: 0,
    totalErrors: 0,
    studentsGrowth: 0,
    paymentsGrowth: 0,
    usersChange: 0,
    activityChange: 0,
    revenueChange: 0,
    errorsChange: 0,
    pendingEnrollments: 0,
    pendingLeaveRequests: 0,
    totalStaff: 0,
    collectedThisMonth: 0,
    incomeThisMonth: 0,
    expenseThisMonth: 0,
    newAdmissionsThisMonth: 0,
  }
}

function num(value: unknown, fallback = 0): number {
  const n = Number(value)
  return Number.isFinite(n) ? n : fallback
}

export function normalizeKpis(raw: Record<string, unknown>): DashboardKpis {
  const attendance = (raw.attendanceSummary ?? {}) as Record<string, unknown>
  const payroll = (raw.payrollSummary ?? {}) as Record<string, unknown>

  return {
    totalStudents: num(raw.totalStudents),
    activeStudents: num(raw.activeStudents),
    totalTeachers: num(raw.totalTeachers),
    totalClasses: num(raw.totalClasses),
    totalParents: num(raw.totalParents),
    totalUsers: num(raw.totalUsers),
    outstandingFees: num(raw.outstandingFees),
    paymentsToday: num(raw.paymentsToday),
    totalRevenue: num(raw.totalRevenue),
    attendanceSummary: {
      present: num(attendance.present),
      absent: num(attendance.absent),
      late: num(attendance.late),
      excused: num(attendance.excused),
      half_day: num(attendance.half_day),
      total: num(attendance.total),
      date: String(attendance.date ?? defaultAttendance.date),
    },
    payrollSummary: {
      total_payroll: num(payroll.total_payroll),
      total_paid: num(payroll.total_paid),
      total_pending: num(payroll.total_pending),
      total_partial: num(payroll.total_partial),
    },
    totalActivity: num(raw.totalActivity),
    totalErrors: num(raw.totalErrors),
    studentsGrowth: num(raw.studentsGrowth),
    paymentsGrowth: num(raw.paymentsGrowth),
    usersChange: num(raw.usersChange),
    activityChange: num(raw.activityChange),
    revenueChange: num(raw.revenueChange),
    errorsChange: num(raw.errorsChange),
    pendingEnrollments: num(raw.pendingEnrollments),
    pendingLeaveRequests: num(raw.pendingLeaveRequests),
    totalStaff: num(raw.totalStaff),
    collectedThisMonth: num(raw.collectedThisMonth),
    incomeThisMonth: num(raw.incomeThisMonth),
    expenseThisMonth: num(raw.expenseThisMonth),
    newAdmissionsThisMonth: num(raw.newAdmissionsThisMonth),
  }
}

export async function fetchKpis(): Promise<DashboardKpis> {
  const { data } = await api.get(e.dashboard.kpis)
  const raw = unwrapOne<Record<string, unknown>>(data)
  if (!raw || typeof raw !== 'object') {
    throw new Error('Dashboard KPIs response was empty or invalid.')
  }
  return normalizeKpis(raw)
}

export async function fetchSchoolWidgets(): Promise<SchoolDashboardWidgets> {
  const { data } = await api.get(e.dashboard.schoolWidgets)
  const raw = unwrapOne<Partial<SchoolDashboardWidgets>>(data) ?? {}
  const charts = raw.charts && typeof raw.charts === 'object' ? raw.charts : null

  return {
    notices: Array.isArray(raw.notices) ? raw.notices : [],
    leave_requests: Array.isArray(raw.leave_requests) ? raw.leave_requests : [],
    upcoming_events: Array.isArray(raw.upcoming_events) ? raw.upcoming_events : [],
    top_teachers: Array.isArray(raw.top_teachers) ? raw.top_teachers : [],
    top_students: Array.isArray(raw.top_students) ? raw.top_students : [],
    new_admissions: Array.isArray(raw.new_admissions) ? raw.new_admissions : [],
    charts: charts
      ? {
          fee_revenue: Array.isArray(charts.fee_revenue) ? charts.fee_revenue : [],
          income_expense: Array.isArray(charts.income_expense) ? charts.income_expense : [],
          admissions_by_class: Array.isArray(charts.admissions_by_class) ? charts.admissions_by_class : [],
          calendar_events: Array.isArray(charts.calendar_events) ? charts.calendar_events : [],
        }
      : {
          fee_revenue: [],
          income_expense: [],
          admissions_by_class: [],
          calendar_events: [],
        },
  }
}

export async function fetchLmsWidgets(): Promise<LmsDashboardWidgets> {
  const { data } = await api.get(e.dashboard.lmsWidgets)
  const raw = unwrapOne<Partial<LmsDashboardWidgets>>(data) ?? {}
  const kpis = raw.kpis && typeof raw.kpis === 'object' ? raw.kpis : {}

  return {
    kpis: {
      total_lessons: Number(kpis.total_lessons ?? 0),
      live_lessons: Number(kpis.live_lessons ?? 0),
      recorded_lessons: Number(kpis.recorded_lessons ?? 0),
      instructors: Number(kpis.instructors ?? 0),
      active_students: Number(kpis.active_students ?? 0),
    },
    upcoming_sessions: Array.isArray(raw.upcoming_sessions) ? raw.upcoming_sessions : [],
    recent_sessions: Array.isArray(raw.recent_sessions) ? raw.recent_sessions : [],
    top_instructors: Array.isArray(raw.top_instructors) ? raw.top_instructors : [],
    sessions_by_type: Array.isArray(raw.sessions_by_type) ? raw.sessions_by_type : [],
  }
}

export async function fetchRolePreview(role: string): Promise<RoleDashboardPreview> {
  const { data } = await api.get(e.dashboard.rolePreview(role))
  const raw = unwrapOne<Partial<RoleDashboardPreview>>(data) ?? {}

  return {
    role: String(raw.role ?? role),
    title: String(raw.title ?? `${role} preview`),
    subtitle: String(raw.subtitle ?? ''),
    kpis: Array.isArray(raw.kpis) ? raw.kpis : [],
    highlights: Array.isArray(raw.highlights) ? raw.highlights : [],
    notices: Array.isArray(raw.notices) ? raw.notices : [],
    upcoming_events: Array.isArray(raw.upcoming_events) ? raw.upcoming_events : [],
  }
}

export async function fetchActivity(days = 30) {
  const { data } = await api.get(e.dashboard.activity, { params: { days } })
  return unwrapList<ActivityPoint>(data)
}

export async function fetchMonthlyStats() {
  const { data } = await api.get(e.dashboard.monthlyStats)
  return unwrapList<MonthlyStat>(data)
}

export async function fetchRecentActivity(limit = 10) {
  const { data } = await api.get(e.dashboard.recentActivity, { params: { limit } })
  return unwrapList<RecentActivityItem>(data)
}

export async function fetchCommandCenter() {
  const { data } = await api.get(e.enterprise.commandCenter)
  return unwrapOne<CommandCenterData>(data)
}

export async function fetchFinanceSummary() {
  const { data } = await api.get(e.finance.summary)
  return unwrapOne<Record<string, unknown>>(data)
}

export async function fetchPendingWorkflows() {
  const { data } = await api.get(e.workflows.pending)
  return unwrapList<Record<string, unknown>>(data)
}

export async function fetchAnalyticsInsights() {
  const { data } = await api.get(e.analytics.insights)
  return unwrapOne<Record<string, unknown>>(data)
}

export async function fetchList<T>(endpoint: string, params?: ListQueryParams) {
  const { data } = await api.get(endpoint, { params })
  return unwrapList<T>(data)
}

export async function fetchPaginatedList<T>(
  endpoint: string,
  params?: ListQueryParams,
): Promise<Paginator<T>> {
  const { data } = await api.get(endpoint, { params })
  return unwrapPaginator<T>(data)
}

export async function fetchOne<T>(endpoint: string, params?: ListQueryParams) {
  const { data } = await api.get(endpoint, { params })
  return unwrapOne<T>(data)
}

export async function createRecord<T>(endpoint: string, payload: Record<string, unknown>) {
  const { data } = await api.post(endpoint, payload)
  return unwrapOne<T>(data)
}

export async function updateRecord<T>(endpoint: string, payload: Record<string, unknown>) {
  const { data } = await api.put(endpoint, payload)
  return unwrapOne<T>(data)
}

export async function deleteRecord(endpoint: string) {
  await api.delete(endpoint)
}

export async function patchRecord<T>(endpoint: string, payload?: Record<string, unknown>) {
  const { data } = await api.patch(endpoint, payload ?? {})
  return unwrapOne<T>(data)
}

export async function postRecord<T>(endpoint: string, payload?: Record<string, unknown>) {
  const { data } = await api.post(endpoint, payload ?? {})
  return unwrapOne<T>(data)
}
