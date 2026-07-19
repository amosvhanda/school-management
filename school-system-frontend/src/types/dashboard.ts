export interface DashboardKpis {
  totalStudents: number
  activeStudents: number
  totalTeachers: number
  totalClasses: number
  totalParents: number
  totalUsers: number
  outstandingFees: number
  paymentsToday: number
  totalRevenue: number
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

export interface AttendanceSummary {
  present: number
  absent: number
  late: number
  excused: number
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
