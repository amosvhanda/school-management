export type AcademicsPeriod = '7d' | '30d' | '90d' | 'all'

export interface AttendanceClassRow {
  class_id?: number
  class_name?: string
  total?: number
  present?: number
  absent?: number
  late?: number
  excused?: number
}

export interface AttendanceReport {
  report_type?: string
  generated_at?: string
  total_records?: number
  by_status?: Record<string, number>
  by_class?: AttendanceClassRow[]
}

export interface AcademicSubjectRow {
  subject?: string
  count?: number
  average_percent?: number
}

export interface AcademicReport {
  report_type?: string
  generated_at?: string
  total_records?: number
  by_subject?: AcademicSubjectRow[]
  by_class?: Array<{ class_id?: number; class_name?: string; average_percent?: number; count?: number }>
}

export interface ExamAnalyticsReport {
  total_records?: number
  by_exam?: Array<{
    exam_id?: number
    exam_name?: string
    academic_year?: string
    students?: number
    average_percent?: number
  }>
  by_subject?: Array<{
    subject?: string
    records?: number
    average_percent?: number
  }>
}

export const ACADEMICS_PERIOD_OPTIONS: Array<{ value: AcademicsPeriod; label: string }> = [
  { value: '7d', label: 'Last 7 days' },
  { value: '30d', label: 'Last 30 days' },
  { value: '90d', label: 'Last 90 days' },
  { value: 'all', label: 'All time' },
]

export function academicsPeriodParams(period: AcademicsPeriod): { from?: string; to?: string } {
  if (period === 'all') return {}

  const days = period === '7d' ? 7 : period === '30d' ? 30 : 90
  const to = new Date()
  const from = new Date()
  from.setDate(from.getDate() - days)

  return {
    from: from.toISOString().slice(0, 10),
    to: to.toISOString().slice(0, 10),
  }
}

export function attendanceRate(row: AttendanceClassRow): number {
  const total = Number(row.total ?? 0)
  if (!total) return 0
  return Math.round((Number(row.present ?? 0) / total) * 100)
}

export function rateAccent(rate: number): 'success' | 'warning' | 'danger' | undefined {
  if (rate >= 90) return 'success'
  if (rate >= 75) return 'warning'
  return 'danger'
}
