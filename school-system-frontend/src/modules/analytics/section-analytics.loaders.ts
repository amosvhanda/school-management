import {
  AlertTriangle,
  Banknote,
  BookOpen,
  Building2,
  CalendarHeart,
  ClipboardCheck,
  DollarSign,
  FileText,
  GraduationCap,
  Megaphone,
  MessageSquare,
  Package,
  Palmtree,
  TrendingUp,
  Users,
} from '@lucide/vue'
import type { SectionKpi } from '@/components/analytics/SectionAnalyticsHub.vue'
import type { SectionKey } from '@/lib/section-hubs'
import { formatMoney } from '@/lib/finance-constants'
import { academicsApi, financeApi, reportsApi } from '@/services/api.service'
import {
  fetchAnalyticsInsights,
  fetchFinanceSummary,
  fetchKpis,
  fetchList,
  fetchPendingWorkflows,
} from '@/services/dashboard.service'
import { moduleEndpoints } from '@/services'
import {
  academicsPeriodParams,
  type AcademicReport,
  type AcademicsPeriod,
  type AttendanceReport,
  type ExamAnalyticsReport,
} from '@/modules/analytics/types/academics-analytics'
import type { CommunicationsDetails } from '@/modules/analytics/types/communications-analytics'
import type {
  AgingReport,
  FeeCollectionPoint,
  FinanceDetails,
  FinanceSummary,
  FinancialReport,
  ReconciliationReport,
} from '@/modules/analytics/types/finance-analytics'
import type { AttendanceSummary, PayrollSummary } from '@/types/dashboard'

interface AnalyticsInsights {
  class_performance?: Array<{ class_name?: string; average_percent?: number }>
  students_at_risk?: unknown[]
  fee_collection_trend?: unknown[]
  assets_needing_replacement?: number
  top_exam_performance?: unknown[]
}

export async function loadSectionKpis(section: SectionKey): Promise<SectionKpi[]> {
  switch (section) {
    case 'people':
      return loadPeopleKpis()
    case 'academics':
      return loadAcademicsKpis()
    case 'finance':
      return loadFinanceKpis()
    case 'hr':
      return loadHrKpis()
    case 'operations':
      return loadOperationsKpis()
    case 'communications':
      return loadCommunicationsKpis()
    default:
      return []
  }
}

async function loadPeopleKpis(): Promise<SectionKpi[]> {
  const [kpis, enrollment] = await Promise.all([
    fetchKpis().catch(() => null),
    fetchList(moduleEndpoints.enrollment, { limit: 200 }).catch(() => []),
  ])
  const pending = (enrollment as Array<{ status?: string }>).filter((r) => r.status === 'pending').length

  return [
    {
      title: 'Active students',
      value: String(kpis?.activeStudents ?? 0),
      subtitle: `${kpis?.totalStudents ?? 0} enrolled`,
      icon: GraduationCap,
      href: '/students',
    },
    {
      title: 'Teachers',
      value: String(kpis?.totalTeachers ?? 0),
      subtitle: 'Teaching staff',
      icon: Users,
      href: '/teachers',
    },
    {
      title: 'Guardians',
      value: String(kpis?.totalParents ?? 0),
      subtitle: 'On file',
      icon: Users,
      href: '/guardians',
    },
    {
      title: 'Pending enrollment',
      value: String(pending),
      subtitle: 'Applications awaiting review',
      icon: ClipboardCheck,
      accent: pending > 0 ? 'warning' : undefined,
      href: '/enrollment',
    },
  ]
}

async function loadAcademicsKpis(): Promise<SectionKpi[]> {
  const [kpis, academicReport, examAnalytics] = await Promise.all([
    fetchKpis().catch(() => null),
    reportsApi.academicPerformance().catch(() => ({})) as Promise<AcademicReport>,
    academicsApi.exams.analytics().catch(() => ({})) as Promise<ExamAnalyticsReport>,
  ])

  const summary = kpis?.attendanceSummary
  const rate = summary?.total
    ? Math.round((summary.present / summary.total) * 100)
    : 0

  const examCount = examAnalytics.by_exam?.length ?? 0
  const avgExam = examAnalytics.by_exam?.length
    ? Math.round(
      examAnalytics.by_exam.reduce((sum, row) => sum + Number(row.average_percent ?? 0), 0)
        / examAnalytics.by_exam.length,
    )
    : null

  return [
    {
      title: 'Today present',
      value: summary ? `${summary.present}/${summary.total}` : '—',
      subtitle: `${rate}% attendance today`,
      icon: ClipboardCheck,
      accent: rate >= 90 ? 'success' : rate >= 75 ? 'warning' : 'danger',
      href: '/academics/attendance',
    },
    {
      title: 'Classes',
      value: String(kpis?.totalClasses ?? 0),
      subtitle: `${kpis?.activeStudents ?? 0} active students`,
      icon: BookOpen,
      href: '/settings?tab=classes',
    },
    {
      title: 'Grade records',
      value: String(academicReport.total_records ?? 0),
      subtitle: 'Marks in gradebook',
      icon: TrendingUp,
      href: '/academics/grades',
    },
    {
      title: 'Exam results',
      value: String(examAnalytics.total_records ?? 0),
      subtitle: examCount
        ? `${examCount} exams · ${avgExam ?? 0}% avg`
        : 'No exam marks yet',
      icon: FileText,
      href: '/academics/exams',
    },
  ]
}

export async function loadAcademicsDetails(period: AcademicsPeriod = '30d'): Promise<{
  attendance: AttendanceReport | null
  academic: AcademicReport | null
  examAnalytics: ExamAnalyticsReport | null
  todayAttendance: AttendanceSummary | null
}> {
  const params = academicsPeriodParams(period)
  const [attendance, academic, examAnalytics, kpis] = await Promise.all([
    reportsApi.attendance(params).catch(() => null) as Promise<AttendanceReport | null>,
    reportsApi.academicPerformance(params).catch(() => null) as Promise<AcademicReport | null>,
    academicsApi.exams.analytics().catch(() => null) as Promise<ExamAnalyticsReport | null>,
    fetchKpis().catch(() => null),
  ])

  return {
    attendance,
    academic,
    examAnalytics,
    todayAttendance: kpis?.attendanceSummary ?? null,
  }
}

async function loadFinanceKpis(): Promise<SectionKpi[]> {
  const [summary, kpis] = await Promise.all([
    fetchFinanceSummary().catch(() => null) as Promise<FinanceSummary | null>,
    fetchKpis().catch(() => null),
  ])
  const currency = String(summary?.currency ?? 'USD')
  const payrollPending =
    (kpis?.payrollSummary.total_pending ?? 0) + (kpis?.payrollSummary.total_partial ?? 0)

  return [
    {
      title: 'Outstanding',
      value: formatMoney(summary?.totalOutstanding ?? kpis?.outstandingFees ?? 0, currency),
      subtitle: 'Unpaid invoice balances',
      icon: DollarSign,
      accent: 'danger',
      href: '/finance/invoices',
    },
    {
      title: 'Collected today',
      value: formatMoney(summary?.collectedToday ?? kpis?.paymentsToday ?? 0, currency),
      subtitle: 'Completed payments today',
      icon: TrendingUp,
      accent: 'success',
      href: '/finance/payments',
    },
    {
      title: 'Overdue invoices',
      value: String(summary?.overdueInvoices ?? 0),
      subtitle: `${summary?.pendingInvoices ?? 0} pending · ${summary?.partialInvoices ?? 0} partial`,
      icon: AlertTriangle,
      accent: (summary?.overdueInvoices ?? 0) > 0 ? 'warning' : undefined,
      href: '/finance/reports',
    },
    {
      title: 'Payroll pending',
      value: formatMoney(payrollPending, currency),
      subtitle: 'Staff salaries due',
      icon: Banknote,
      accent: payrollPending > 0 ? 'warning' : undefined,
      href: '/finance/payroll',
    },
  ]
}

export async function loadFinanceDetails(): Promise<FinanceDetails> {
  const [summary, financial, aging, reconciliation, insights, kpis] = await Promise.all([
    fetchFinanceSummary().catch(() => null) as Promise<FinanceSummary | null>,
    reportsApi.financial().catch(() => null) as Promise<FinancialReport | null>,
    financeApi.aging().catch(() => null) as Promise<AgingReport | null>,
    financeApi.reconciliation({ period: 'monthly' }).catch(() => null) as Promise<ReconciliationReport | null>,
    fetchAnalyticsInsights().catch(() => null),
    fetchKpis().catch(() => null),
  ])

  const feeTrend = Array.isArray(insights?.fee_collection_trend)
    ? (insights.fee_collection_trend as FeeCollectionPoint[])
    : []

  return {
    summary,
    financial,
    aging,
    reconciliation,
    feeTrend,
    payroll: (kpis?.payrollSummary ?? null) as PayrollSummary | null,
  }
}

async function loadHrKpis(): Promise<SectionKpi[]> {
  const [leave, discipline, workflows, policies] = await Promise.all([
    fetchList(moduleEndpoints.leaveRequests, { limit: 200 }).catch(() => []),
    fetchList(moduleEndpoints.discipline, { limit: 200 }).catch(() => []),
    fetchPendingWorkflows().catch(() => []),
    fetchList(moduleEndpoints.compliancePolicies, { limit: 200 }).catch(() => []),
  ])

  const pendingLeave = (leave as Array<{ status?: string }>).filter((r) => r.status === 'pending').length
  const openDiscipline = (discipline as Array<{ status?: string }>).filter(
    (r) => r.status !== 'closed' && r.status !== 'resolved',
  ).length

  return [
    {
      title: 'Leave pending',
      value: String(pendingLeave),
      subtitle: 'Awaiting approval',
      icon: Palmtree,
      accent: pendingLeave > 0 ? 'warning' : undefined,
      href: '/hr/leave',
    },
    {
      title: 'Discipline cases',
      value: String(openDiscipline),
      subtitle: 'Open records',
      icon: AlertTriangle,
      href: '/hr/discipline',
    },
    {
      title: 'Workflows',
      value: String(workflows.length),
      subtitle: 'Pending approvals',
      icon: ClipboardCheck,
      href: '/workflows',
    },
    {
      title: 'Policies',
      value: String(policies.length),
      subtitle: 'Compliance documents',
      icon: Building2,
      href: '/hr?tab=policies',
    },
  ]
}

async function loadOperationsKpis(): Promise<SectionKpi[]> {
  const [insights, inventory, visitors, events] = await Promise.all([
    fetchAnalyticsInsights().catch(() => ({})) as Promise<AnalyticsInsights>,
    fetchList(moduleEndpoints.inventoryItems, { limit: 200 }).catch(() => []),
    fetchList(moduleEndpoints.visitors, { limit: 200 }).catch(() => []),
    fetchList(moduleEndpoints.events, { limit: 200 }).catch(() => []),
  ])

  const today = new Date().toISOString().slice(0, 10)
  const visitorsToday = (visitors as Array<{ check_in?: string; created_at?: string }>).filter((v) =>
    String(v.check_in ?? v.created_at ?? '').slice(0, 10) === today,
  ).length

  return [
    {
      title: 'Inventory items',
      value: String(inventory.length),
      subtitle: 'Stock lines',
      icon: Package,
      href: '/operations/inventory',
    },
    {
      title: 'Visitors today',
      value: String(visitorsToday),
      subtitle: 'Campus sign-ins',
      icon: Users,
      href: '/operations/front-office',
    },
    {
      title: 'Upcoming events',
      value: String(events.length),
      subtitle: 'Scheduled on calendar',
      icon: CalendarHeart,
      href: '/operations/events',
    },
    {
      title: 'Assets aging',
      value: String(insights.assets_needing_replacement ?? 0),
      subtitle: 'Need replacement',
      icon: Building2,
      accent: (insights.assets_needing_replacement ?? 0) > 0 ? 'warning' : undefined,
      href: '/operations/assets',
    },
  ]
}

async function loadCommunicationsKpis(): Promise<SectionKpi[]> {
  const details = await loadCommunicationsDetails()

  return [
    {
      title: 'Announcements',
      value: String(details.announcements.length),
      subtitle: `${details.activeAnnouncements} active`,
      icon: Megaphone,
      href: '/communications/announcements',
    },
    {
      title: 'Message threads',
      value: String(details.threads.length),
      subtitle: `${details.openThreads} open`,
      icon: MessageSquare,
      href: '/communications/threads',
    },
    {
      title: 'Awaiting staff',
      value: String(details.unassignedThreads),
      subtitle: 'Parent threads not yet assigned',
      icon: Users,
      accent: details.unassignedThreads > 0 ? 'warning' : undefined,
      href: '/communications/threads',
    },
    {
      title: 'Staff inbox',
      value: 'Open',
      subtitle: 'Reply to parent messages',
      icon: MessageSquare,
      href: '/communications/threads',
    },
  ]
}

export async function loadCommunicationsDetails(): Promise<CommunicationsDetails> {
  const [announcements, threads] = await Promise.all([
    fetchList(moduleEndpoints.announcements, { limit: 200 }).catch(() => []),
    fetchList(moduleEndpoints.threads, { limit: 200 }).catch(() => []),
  ])

  const threadRows = threads as Array<{ status?: string; staff_user_id?: number | null }>
  const openThreads = threadRows.filter((t) => t.status !== 'closed').length
  const unassignedThreads = threadRows.filter(
    (t) => t.status !== 'closed' && !t.staff_user_id,
  ).length
  const activeAnnouncements = (announcements as Array<{ is_active?: boolean }>).filter(
    (a) => a.is_active !== false,
  ).length

  return {
    threads: threads as CommunicationsDetails['threads'],
    announcements: announcements as CommunicationsDetails['announcements'],
    unassignedThreads,
    openThreads,
    activeAnnouncements,
  }
}

export async function loadSectionDetails(section: SectionKey): Promise<Record<string, unknown>> {
  switch (section) {
    case 'academics':
      return loadAcademicsDetails()
    case 'finance':
      return loadFinanceDetails() as unknown as Record<string, unknown>
    case 'communications': {
      const details = await loadCommunicationsDetails()
      return { ...details }
    }
    case 'people':
    case 'hr':
    case 'operations':
      return {}
    default:
      return {}
  }
}
