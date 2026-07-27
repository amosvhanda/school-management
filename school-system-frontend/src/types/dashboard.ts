export interface DashboardKpis {
  totalStudents: number
  activeStudents: number
  totalTeachers: number
  totalStaff?: number
  totalClasses: number
  totalParents: number
  totalUsers: number
  outstandingFees: number
  paymentsToday: number
  totalRevenue: number
  collectedThisMonth?: number
  incomeThisMonth?: number
  expenseThisMonth?: number
  newAdmissionsThisMonth?: number
  attendanceSummary: AttendanceSummary
  payrollSummary: PayrollSummary
  totalActivity: number
  totalErrors: number
  studentsGrowth: number
  paymentsGrowth: number
  usersChange: number
  activityChange: number
  revenueChange: number
  errorsChange: number
  pendingEnrollments?: number
  pendingLeaveRequests?: number
}

export interface SchoolDashboardNotice {
  id: number
  title: string
  message: string
  author: string
  date: string | null
}

export interface SchoolDashboardLeaveRequest {
  id: number
  teacher_name: string
  department?: string | null
  type: string
  days: number
  start_date: string | null
  end_date: string | null
  applied_on: string | null
}

export interface SchoolDashboardEvent {
  id: number
  title: string
  location?: string | null
  starts_at: string | null
  ends_at: string | null
  type?: string | null
}

export interface SchoolDashboardPerson {
  id: number
  name: string
  email?: string | null
  subject?: string | null
  department?: string | null
  class_name?: string | null
  marks?: number | null
  result_count?: number
  joined_on?: string | null
}

export interface SchoolDashboardFeeRevenuePoint {
  month: string
  total_fee: number
  collected: number
}

export interface SchoolDashboardIncomeExpensePoint {
  month: string
  income: number
  expense: number
}

export interface SchoolDashboardAdmissionSlice {
  label: string
  value: number
}

export interface SchoolDashboardCalendarEvent {
  id: number
  title: string
  starts_at: string | null
  type?: string | null
}

export interface SchoolDashboardCharts {
  fee_revenue: SchoolDashboardFeeRevenuePoint[]
  income_expense: SchoolDashboardIncomeExpensePoint[]
  admissions_by_class: SchoolDashboardAdmissionSlice[]
  calendar_events: SchoolDashboardCalendarEvent[]
}

export interface SchoolDashboardWidgets {
  notices: SchoolDashboardNotice[]
  leave_requests: SchoolDashboardLeaveRequest[]
  upcoming_events: SchoolDashboardEvent[]
  top_teachers: SchoolDashboardPerson[]
  top_students: SchoolDashboardPerson[]
  new_admissions: SchoolDashboardPerson[]
  charts?: SchoolDashboardCharts | null
}

export interface LmsDashboardSession {
  id: number
  title: string
  lesson_type?: string | null
  status?: string | null
  scheduled_at?: string | null
  teacher_name?: string | null
  class_name?: string | null
}

export interface LmsDashboardInstructor {
  id: number
  name: string
  email?: string | null
  subject?: string | null
  lesson_count: number
}

export interface LmsDashboardWidgets {
  kpis: {
    total_lessons: number
    live_lessons: number
    recorded_lessons: number
    instructors: number
    active_students: number
  }
  upcoming_sessions: LmsDashboardSession[]
  recent_sessions: LmsDashboardSession[]
  top_instructors: LmsDashboardInstructor[]
  sessions_by_type: Array<{ label: string; value: number }>
}

export interface RoleDashboardPreview {
  role: string
  title: string
  subtitle: string
  kpis: Array<{ label: string; value: string | number }>
  highlights: string[]
  notices: Array<{ id: number; title: string; message?: string; date?: string | null }>
  upcoming_events: Array<{
    id: number
    title: string
    starts_at?: string | null
    location?: string | null
  }>
}

export interface AttendanceSummary {
  present: number
  absent: number
  late: number
  excused: number
  half_day?: number
  total: number
  date: string
}

export interface PayrollSummary {
  total_payroll: number
  total_paid: number
  total_pending: number
  total_partial: number
}

export interface ActivityPoint {
  date: string
  value: number
  label?: string
}

export interface MonthlyStat {
  month: string
  value: number
  category: 'Revenue' | 'Users' | 'Transactions'
}

export interface RecentActivityItem {
  id: number | string
  type: string
  action: string
  user: string
  description: string
  timestamp: string
  status: string
}

export interface CommandCenterData {
  timestamp: string
  school_health: {
    active_students: number
    attendance_anomalies: number
    average_performance: number
  }
  financial_status: {
    outstanding_fees: number
    collected_this_month: number
    cashflow_forecast?: Record<string, unknown>
  }
  academic_heatmap: Array<{ subject_id: number; average_percent: number }>
  approval_queue: Array<Record<string, unknown>>
  risk_alerts: Array<Record<string, unknown>>
  department_comparison: Array<{ department: string; teachers: number }>
  kpi_scorecard: {
    enrollment: number
    fee_collection_rate: number
    pending_leave: number
  }
}
