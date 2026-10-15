<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  BookOpen,
  CalendarDays,
  ClipboardCheck,
  Download,
  FileSpreadsheet,
  FileText,
  NotebookPen,
  Receipt,
  TrendingUp,
} from '@lucide/vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { studentPortalApi, studentsApi } from '@/services/api.service'
import { getErrorMessage, unwrapList } from '@/lib/api-response'
import { formatDate, formatTime } from '@/lib/format'
import { formatMoney } from '@/lib/finance-constants'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import StudentGreetingCard from '@/components/dashboard/StudentGreetingCard.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import StudentCbtPanel from '@/modules/student-portal/components/StudentCbtPanel.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Textarea } from '@/components/ui/textarea'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { cn } from '@/lib/utils'

const route = useRoute()
const { user } = useAuth()
const toast = useToast()

const loading = ref(false)
const downloading = ref(false)
const error = ref<string | null>(null)
const lastUpdated = ref<Date | null>(null)

const studentProfile = ref<Record<string, unknown> | null>(null)
const attendanceSummary = ref<Record<string, unknown> | null>(null)
const attendanceRows = ref<Array<Record<string, unknown>>>([])
const performanceRows = ref<Array<Record<string, unknown>>>([])
const examRows = ref<Array<Record<string, unknown>>>([])
const invoiceRows = ref<Array<Record<string, unknown>>>([])
const assignmentRows = ref<Array<Record<string, unknown>>>([])
const announcementRows = ref<Array<Record<string, unknown>>>([])
const timetableRows = ref<Array<Record<string, unknown>>>([])
const expandedAnnouncementId = ref<number | string | null>(null)
const assignmentSheetOpen = ref(false)
const selectedAssignment = ref<Record<string, unknown> | null>(null)
const submitContent = ref('')
const submitFile = ref<File | null>(null)
const submitting = ref(false)
const submitError = ref('')

const section = computed(() => {
  if (route.path === '/student/performance') return 'performance'
  if (route.path === '/student/attendance') return 'attendance'
  if (route.path === '/student/exams') return 'exams'
  if (route.path === '/student/fees') return 'fees'
  if (route.path === '/student/assignments') return 'assignments'
  if (route.path === '/student/announcements') return 'announcements'
  return 'dashboard'
})

const pageTitle = computed(() => {
  switch (section.value) {
    case 'performance':
      return 'My performance'
    case 'attendance':
      return 'Student Attendance'
    case 'exams':
      return 'My exams'
    case 'fees':
      return 'My fees'
    case 'assignments':
      return 'My assignments'
    case 'announcements':
      return 'Announcements'
    default:
      return 'Student Dashboard'
  }
})

const pageDescription = computed(() => {
  switch (section.value) {
    case 'performance':
      return 'Your continuous assessment marks by subject and term.'
    case 'attendance':
      return 'Your presence, late arrivals, and absences this term.'
    case 'exams':
      return 'Published examination timetable and results.'
    case 'fees':
      return 'Invoices and outstanding balances.'
    case 'assignments':
      return 'Homework and classwork set by your teachers.'
    case 'announcements':
      return 'School notices for students.'
    default:
      return 'Your school records at a glance.'
  }
})

const studentId = computed<number | null>(() => {
  const value = user.value?.student_id
  return typeof value === 'number' ? value : null
})

const attendanceRate = computed(() => {
  const value = Number(attendanceSummary.value?.attendance_rate ?? 0)
  return Number.isFinite(value) ? value : 0
})

const attendanceBreakdown = computed(() => {
  const summary = attendanceSummary.value ?? {}
  const present = Number(summary.present ?? 0)
  const late = Number(summary.late ?? 0)
  const absent = Number(summary.absent ?? 0)
  const excused = Number(summary.excused ?? 0)
  const totalDays = Number(summary.total_days ?? 0)
  const counted = present + late + absent + excused

  return {
    present,
    late,
    absent,
    excused,
    totalDays,
    counted,
  }
})

const attendanceHeatmap = computed(() => {
  const byDate = new Map<string, string>()
  for (const row of attendanceRows.value) {
    const date = String(row.date ?? '').slice(0, 10)
    if (date) byDate.set(date, String(row.status ?? ''))
  }
  const days: Array<{ date: string; status: string; label: string }> = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  for (let i = 29; i >= 0; i -= 1) {
    const day = new Date(today)
    day.setDate(today.getDate() - i)
    const iso = `${day.getFullYear()}-${String(day.getMonth() + 1).padStart(2, '0')}-${String(day.getDate()).padStart(2, '0')}`
    days.push({
      date: iso,
      status: byDate.get(iso) ?? '',
      label: formatDate(iso),
    })
  }
  return days
})

function attendanceCellClass(status: string) {
  if (status === 'present') return 'bg-chart-2'
  if (status === 'late') return 'bg-chart-3'
  if (status === 'absent') return 'bg-destructive/80'
  if (status === 'excused' || status === 'sick') return 'bg-primary/70'
  return 'bg-muted'
}

const averageScore = computed(() => {
  if (!performanceRows.value.length) return 0
  const scored = performanceRows.value.filter((row) => row.score != null)
  if (!scored.length) return 0
  const total = scored.reduce((sum, row) => sum + Number(row.score ?? 0), 0)
  return total / scored.length
})

const totalOutstanding = computed(() =>
  invoiceRows.value.reduce((sum, row) => sum + Number(row.balance ?? 0), 0),
)

const pendingInvoices = computed(() =>
  invoiceRows.value.filter((row) => String(row.status ?? '').toLowerCase() !== 'paid').length,
)

const hasPerformanceData = computed(() =>
  performanceRows.value.some((row) => row.score != null),
)

const hasAttendanceData = computed(() =>
  Boolean(attendanceSummary.value) && attendanceBreakdown.value.counted > 0,
)

const pendingAssignments = computed(() =>
  assignmentRows.value.filter((row) => canSubmitAssignment(row.submission_status ?? row.status)).length,
)

const homeworkDueSoon = computed(() =>
  assignmentRows.value.filter((row) => assignmentDueTone(row) !== 'neutral').length,
)

const upcomingExamCount = computed(() =>
  examRows.value.filter((row) => !row.result && row.exam_date).length,
)

const overviewCards = computed(() => [
  {
    title: 'Average score',
    value: hasPerformanceData.value ? `${averageScore.value.toFixed(1)}%` : '—',
    subtitle: hasPerformanceData.value ? 'Across recorded assessments' : 'No assessments yet',
    icon: TrendingUp,
    empty: !hasPerformanceData.value,
    href: '/student/performance',
  },
  {
    title: 'Attendance',
    value: hasAttendanceData.value ? `${attendanceRate.value.toFixed(1)}%` : '—',
    subtitle: hasAttendanceData.value ? 'Current attendance summary' : 'No attendance marked yet',
    icon: ClipboardCheck,
    accent: hasAttendanceData.value && attendanceRate.value < 80 ? ('warning' as const) : ('success' as const),
    empty: !hasAttendanceData.value,
    href: '/student/attendance',
  },
  {
    title: 'Assignments pending',
    value: assignmentRows.value.length ? pendingAssignments.value : '—',
    subtitle: 'Still to submit',
    icon: NotebookPen,
    empty: !assignmentRows.value.length,
    href: '/student/assignments',
  },
  {
    title: 'Homework due',
    value: assignmentRows.value.length ? homeworkDueSoon.value : '—',
    subtitle: 'Due today or this week',
    icon: BookOpen,
    empty: !assignmentRows.value.length,
    href: '/student/assignments',
  },
  {
    title: 'Upcoming exams',
    value: upcomingExamCount.value || '—',
    subtitle: 'Published timetable',
    icon: FileText,
    empty: !upcomingExamCount.value,
    href: '/student/exams',
  },
  {
    title: 'Fee due',
    value: invoiceRows.value.length || totalOutstanding.value > 0
      ? formatMoney(totalOutstanding.value)
      : '—',
    subtitle: pendingInvoices.value ? `${pendingInvoices.value} unpaid invoices` : 'Nothing outstanding',
    icon: Receipt,
    accent: totalOutstanding.value > 0 ? ('danger' as const) : undefined,
    empty: !invoiceRows.value.length && totalOutstanding.value <= 0,
    href: '/student/fees',
  },
])

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as const

function clock(value?: unknown) {
  return formatTime(value, '—')
}

function toMinutes(hhmm: string) {
  const [h, m] = hhmm.split(':').map(Number)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return null
  return h * 60 + m
}

function lessonStatus(start: string, end: string): 'completed' | 'current' | 'upcoming' {
  const now = new Date()
  const current = now.getHours() * 60 + now.getMinutes()
  const s = toMinutes(start)
  const e = toMinutes(end)
  if (s == null || e == null) return 'upcoming'
  if (current >= e) return 'completed'
  if (current >= s && current < e) return 'current'
  return 'upcoming'
}

const todayLessons = computed(() => {
  const todayName = DAYS[new Date().getDay()]
  return timetableRows.value
    .filter((slot) => String(slot.day ?? '') === todayName)
    .map((slot) => {
      const start = clock(slot.start_time)
      const end = clock(slot.end_time)
      const teacher = asRecord(slot.teacher)
      const room = typeof slot.room === 'string'
        ? slot.room
        : String(asRecord(slot.room)?.name ?? '—')
      const subject = typeof slot.subject === 'string'
        ? slot.subject
        : String(asRecord(slot.subject)?.name ?? slot.subject_name ?? 'Lesson')
      return {
        id: Number(slot.id ?? 0),
        start,
        end,
        subject,
        teacher: String(teacher?.name ?? '—'),
        room,
        status: start !== '—' && end !== '—' ? lessonStatus(start, end) : ('upcoming' as const),
        sortKey: toMinutes(start) ?? 9999,
      }
    })
    .sort((a, b) => a.sortKey - b.sortKey)
})

const subjectPerformance = computed(() => {
  const grouped = new Map<string, number[]>()
  for (const row of performanceRows.value) {
    if (row.score == null) continue
    const subject = String(row.subject ?? 'Subject')
    const list = grouped.get(subject) ?? []
    const total = Number(row.total ?? 0)
    const score = Number(row.score ?? 0)
    list.push(total > 0 ? Math.round((score / total) * 100) : score)
    grouped.set(subject, list)
  }
  return [...grouped.entries()]
    .map(([subject, scores]) => ({
      subject,
      percent: Math.round(scores.reduce((sum, n) => sum + n, 0) / scores.length),
    }))
    .sort((a, b) => b.percent - a.percent)
    .slice(0, 6)
})

const studentDisplayName = computed(() =>
  String(studentProfile.value?.full_name ?? user.value?.name ?? 'Student'),
)

const studentHouse = computed(() => {
  const nested = asRecord(studentProfile.value?.house)
  return String(studentProfile.value?.house_name ?? nested?.name ?? '').trim()
})

const quickActions = [
  { label: 'View Timetable', href: '/student', icon: CalendarDays },
  { label: 'Assignments', href: '/student/assignments', icon: BookOpen },
  { label: 'Exam Results', href: '/student/exams', icon: FileText },
  { label: 'Pay Fees', href: '/student/fees', icon: Receipt },
  { label: 'Attendance', href: '/student/attendance', icon: ClipboardCheck },
]

const attentionItems = computed(() => {
  const items: Array<{ title: string; detail: string; href: string }> = []

  if (totalOutstanding.value > 0) {
    items.push({
      title: 'Outstanding fees',
      detail: `${formatMoney(totalOutstanding.value)} still due`,
      href: '/student/fees',
    })
  }

  if (pendingInvoices.value > 0 && totalOutstanding.value <= 0) {
    items.push({
      title: 'Pending invoices',
      detail: `${pendingInvoices.value} invoice${pendingInvoices.value === 1 ? '' : 's'} need attention`,
      href: '/student/fees',
    })
  }

  if (hasAttendanceData.value && attendanceRate.value < 80) {
    items.push({
      title: 'Attendance below 80%',
      detail: `Current rate ${attendanceRate.value.toFixed(1)}%`,
      href: '/student/attendance',
    })
  }

  const upcomingExam = examRows.value.find((row) => !row.result && row.exam_date)
  if (upcomingExam) {
    items.push({
      title: 'Upcoming exam',
      detail: `${String(upcomingExam.name ?? 'Exam')} · ${formatDate(upcomingExam.exam_date)}`,
      href: '/student/exams',
    })
  }

  const urgentAssignment = assignmentRows.value.find((row) => {
    const due = parseDateOnly(row.due_date)
    if (!due) return false
    const days = daysUntil(due)
    return days <= 7
  })
  if (urgentAssignment) {
    const due = parseDateOnly(urgentAssignment.due_date)
    const days = due ? daysUntil(due) : null
    items.push({
      title: days != null && days < 0 ? 'Overdue assignment' : 'Assignment due soon',
      detail: `${String(urgentAssignment.title ?? 'Assignment')}${due ? ` · ${formatDate(urgentAssignment.due_date)}` : ''}`,
      href: '/student/assignments',
    })
  }

  const recentAnnouncement = announcementRows.value.find((row) => {
    const when = parseDateOnly(row.date ?? row.created_at)
    if (!when) return false
    return daysUntil(when) >= -7 && daysUntil(when) <= 0
  })
  if (recentAnnouncement) {
    items.push({
      title: 'New announcement',
      detail: String(recentAnnouncement.title ?? 'School notice'),
      href: '/student/announcements',
    })
  }

  return items.slice(0, 3)
})

function parseDateOnly(value: unknown): Date | null {
  const raw = String(value ?? '').trim()
  if (!raw) return null
  const dateOnly = raw.match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (dateOnly) {
    const date = new Date(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3]))
    return Number.isNaN(date.getTime()) ? null : date
  }
  const parsed = new Date(raw)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function daysUntil(date: Date): number {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const target = new Date(date)
  target.setHours(0, 0, 0, 0)
  return Math.round((target.getTime() - today.getTime()) / 86_400_000)
}

function assignmentDueLabel(row: Record<string, unknown>): string {
  const due = parseDateOnly(row.due_date)
  if (!due) return 'No due date'
  const days = daysUntil(due)
  if (days < 0) return `Overdue · ${formatDate(row.due_date)}`
  if (days === 0) return `Due today · ${formatDate(row.due_date)}`
  if (days <= 7) return `Due in ${days} day${days === 1 ? '' : 's'} · ${formatDate(row.due_date)}`
  return formatDate(row.due_date)
}

function assignmentDueTone(row: Record<string, unknown>): 'danger' | 'warning' | 'neutral' {
  const due = parseDateOnly(row.due_date)
  if (!due) return 'neutral'
  const days = daysUntil(due)
  if (days < 0) return 'danger'
  if (days <= 7) return 'warning'
  return 'neutral'
}

function submissionLabel(status: unknown): string {
  const value = String(status ?? '').trim()
  if (!value) return 'Not submitted'
  const labels: Record<string, string> = {
    submitted: 'Submitted',
    graded: 'Graded',
    returned: 'Returned',
    resubmit: 'Resubmit requested',
  }
  return labels[value] ?? value
}

function canSubmitAssignment(status: unknown): boolean {
  const value = String(status ?? '').trim()
  return !value || value === 'returned' || value === 'resubmit'
}

function openAssignmentSheet(row: Record<string, unknown>) {
  selectedAssignment.value = row
  submitContent.value = ''
  submitFile.value = null
  submitError.value = ''
  assignmentSheetOpen.value = true
}

function onSubmitFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  submitFile.value = input.files?.[0] ?? null
}

async function handleSubmitAssignment() {
  const assignment = selectedAssignment.value
  const id = assignment?.id
  if (id == null) return

  if (!submitContent.value.trim() && !submitFile.value) {
    submitError.value = 'Add written work or attach a file before submitting.'
    return
  }

  submitting.value = true
  submitError.value = ''
  try {
    await studentPortalApi.submitAssignment(id as number | string, {
      content: submitContent.value.trim() || undefined,
      file: submitFile.value,
    })
    assignmentSheetOpen.value = false
    toast.success('Assignment submitted')
    await loadStudentPortal()
  } catch (err) {
    submitError.value = getErrorMessage(err, 'Unable to submit assignment.')
  } finally {
    submitting.value = false
  }
}

function announcementTypeLabel(type: unknown): string {
  const value = String(type ?? 'general').trim()
  if (!value) return 'General'
  return value.charAt(0).toUpperCase() + value.slice(1)
}

function toggleAnnouncement(id: number | string) {
  expandedAnnouncementId.value = expandedAnnouncementId.value === id ? null : id
}

function asRecord(value: unknown): Record<string, unknown> | null {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return null
  return value as Record<string, unknown>
}

function asRecordArray(value: unknown): Array<Record<string, unknown>> {
  if (!Array.isArray(value)) return []
  return value.filter((item): item is Record<string, unknown> => !!item && typeof item === 'object' && !Array.isArray(item))
}

function examResultSummary(row: Record<string, unknown>): string {
  const result = asRecord(row.result)
  if (!result) return row.is_published ? 'Awaiting marks' : 'Not published'
  const grade = String(result.grade ?? '—')
  const percentage = result.percentage == null ? '—' : `${result.percentage}%`
  return `${grade} · ${percentage}`
}

function triggerBlobDownload(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}

async function downloadResults(format: 'html' | 'csv') {
  if (!studentId.value) return
  downloading.value = true
  try {
    const blob = await studentsApi.downloadResults(studentId.value, format)
    const number = String(studentProfile.value?.student_number ?? 'student')
    const filename = format === 'csv'
      ? `${number}_results.csv`
      : `${number}_report_card.html`
    triggerBlobDownload(blob, filename)
    toast.success(format === 'csv' ? 'Results CSV downloaded' : 'Report card downloaded')
  } catch (err) {
    toast.error('Download failed', getErrorMessage(err))
  } finally {
    downloading.value = false
  }
}

async function loadStudentPortal() {
  loading.value = true
  error.value = null

  try {
    // Prefer portal endpoints (no client-supplied student id; resolves link server-side).
    const portal = asRecord(await studentPortalApi.dashboard())
    const me = asRecord(portal?.student) ?? asRecord(await studentPortalApi.me())
    if (me) {
      studentProfile.value = {
        ...me,
        class: me.class_name ?? me.class,
      }
    }

    const summaryFromPortal = asRecord(portal?.attendance_summary)
    if (summaryFromPortal) {
      attendanceSummary.value = summaryFromPortal
    }

    if (Array.isArray(portal?.upcoming_exams) && portal.upcoming_exams.length) {
      examRows.value = asRecordArray(portal.upcoming_exams).map((row) => ({
        ...row,
        is_published: row.is_published ?? false,
        result: null,
      }))
    }

    if (Array.isArray(portal?.open_invoices)) {
      invoiceRows.value = asRecordArray(portal.open_invoices)
    }

    if (Array.isArray(portal?.assignments)) {
      assignmentRows.value = asRecordArray(portal.assignments)
    }

    if (Array.isArray(portal?.announcements)) {
      announcementRows.value = asRecordArray(portal.announcements)
    }

    const [gradesPayload, feesPayload, attendancePayload, assignmentsPayload, announcementsPayload, timetablePayload] = await Promise.allSettled([
      studentPortalApi.grades(),
      studentPortalApi.fees(),
      studentPortalApi.attendance(),
      studentPortalApi.assignments(),
      studentPortalApi.announcements(),
      studentPortalApi.timetable(),
    ])

    if (gradesPayload.status === 'fulfilled') {
      const data = asRecord(gradesPayload.value)
      performanceRows.value = asRecordArray(data?.grades ?? gradesPayload.value)
      const examResults = asRecordArray(data?.exam_results)
      if (examResults.length) {
        examRows.value = examResults.map((row) => {
          const exam = asRecord(row.exam)
          return {
            ...row,
            name: exam?.name ?? row.name,
            exam_date: exam?.exam_date ?? row.exam_date,
            is_published: true,
            result: {
              grade: row.grade,
              percentage: row.percentage ?? row.marks,
            },
          }
        })
      }
    }

    if (feesPayload.status === 'fulfilled') {
      const data = asRecord(feesPayload.value)
      invoiceRows.value = asRecordArray(data?.invoices ?? feesPayload.value)
    }

    if (attendancePayload.status === 'fulfilled') {
      const data = asRecord(attendancePayload.value)
      const summary = asRecord(data?.summary)
      const rows = asRecordArray(data?.rows ?? attendancePayload.value)
      attendanceRows.value = rows
      if (summary) {
        attendanceSummary.value = summary
      } else if (!attendanceSummary.value) {
        const present = rows.filter((r) => r.status === 'present').length
        const late = rows.filter((r) => r.status === 'late').length
        const absent = rows.filter((r) => r.status === 'absent').length
        const excused = rows.filter((r) => r.status === 'excused').length
        const total = rows.length
        attendanceSummary.value = {
          present,
          late,
          absent,
          excused,
          total_days: total,
          attendance_rate: total ? ((present + late) / total) * 100 : 0,
        }
      }
    }

    if (assignmentsPayload.status === 'fulfilled') {
      assignmentRows.value = asRecordArray(assignmentsPayload.value)
    }

    if (announcementsPayload.status === 'fulfilled') {
      announcementRows.value = asRecordArray(announcementsPayload.value)
    }

    if (timetablePayload.status === 'fulfilled') {
      timetableRows.value = unwrapList<Record<string, unknown>>(timetablePayload.value)
    }

    if (!studentProfile.value) {
      error.value = 'Student profile is not linked to this account yet. Please contact the school office.'
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Could not load student records at the moment.')
  } finally {
    lastUpdated.value = new Date()
    loading.value = false
  }
}

function refresh() {
  return loadStudentPortal()
}

onMounted(loadStudentPortal)
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] space-y-6 pb-8">
    <header
      class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
    >
      <div class="min-w-0 space-y-1">
        <p class="text-xs text-muted-foreground">Dashboard / {{ pageTitle }}</p>
        <h1 class="font-heading text-2xl font-semibold tracking-tight text-foreground md:text-3xl">
          {{ pageTitle }}
        </h1>
        <p class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
          {{ pageDescription }}
        </p>
      </div>
      <div
        v-if="studentId && (section === 'dashboard' || section === 'performance' || section === 'exams')"
        class="flex flex-wrap gap-2"
      >
        <Button
          variant="outline"
          size="sm"
          class="h-9 rounded-full"
          :disabled="downloading || loading"
          @click="downloadResults('html')"
        >
          <Download class="mr-2 size-4" aria-hidden="true" />
          Report card
        </Button>
        <Button
          variant="outline"
          size="sm"
          class="h-9 rounded-full"
          :disabled="downloading || loading"
          @click="downloadResults('csv')"
        >
          <FileSpreadsheet class="mr-2 size-4" aria-hidden="true" />
          CSV
        </Button>
        <Button
          v-if="section === 'dashboard'"
          variant="outline"
          size="sm"
          class="h-9 rounded-full"
          :disabled="loading"
          :aria-busy="loading"
          @click="refresh"
        >
          Refresh
        </Button>
      </div>
    </header>

    <ErrorState
      v-if="!loading && error"
      :title="studentProfile ? 'Could not load records' : 'Student profile not linked'"
      :description="error"
      @retry="refresh"
    />

    <DashboardSkeleton v-else-if="section === 'dashboard' && loading" />

    <template v-else-if="section === 'dashboard'">
      <StudentGreetingCard
        :name="studentDisplayName"
        :student-number="String(studentProfile?.student_number ?? '')"
        :class-name="String(studentProfile?.class ?? '')"
        :house="studentHouse"
        :attendance-rate="hasAttendanceData ? `${attendanceRate.toFixed(1)}%` : '—'"
        :average-score="hasPerformanceData ? `${averageScore.toFixed(1)}%` : '—'"
        :pending-assignments="pendingAssignments"
        :fee-due="totalOutstanding > 0 ? formatMoney(totalOutstanding) : '—'"
      />

      <section aria-labelledby="student-quick-actions">
        <h2 id="student-quick-actions" class="mb-3 text-sm font-medium text-muted-foreground">
          Quick actions
        </h2>
        <div class="flex flex-wrap gap-2">
          <Button
            v-for="action in quickActions"
            :key="action.label"
            variant="outline"
            size="sm"
            class="h-9 rounded-full"
            as-child
          >
            <RouterLink :to="action.href">
              <component :is="action.icon" class="mr-1.5 size-3.5" aria-hidden="true" />
              {{ action.label }}
            </RouterLink>
          </Button>
        </div>
      </section>

      <section aria-label="Student overview">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <KpiCard v-for="card in overviewCards" :key="card.title" v-bind="card" />
        </div>
      </section>

      <section class="grid gap-4 xl:grid-cols-12" aria-label="Schedule and academic performance">
        <Card class="xl:col-span-7">
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Today's Schedule</CardTitle>
              <CardDescription>{{ DAYS[new Date().getDay()] }} · {{ todayLessons.length }} periods</CardDescription>
            </div>
          </CardHeader>
          <CardContent class="space-y-3">
            <p v-if="!todayLessons.length" class="py-8 text-center text-sm text-muted-foreground">
              No lessons on your class timetable for today.
            </p>
            <ol v-else class="space-y-3">
              <li
                v-for="lesson in todayLessons"
                :key="lesson.id"
                class="flex items-start gap-3 rounded-2xl bg-muted/40 px-4 py-3 ring-1 ring-border/60"
              >
                <div class="w-16 shrink-0 text-sm font-semibold tabular-nums">{{ lesson.start }}</div>
                <div class="min-w-0 flex-1">
                  <p class="font-semibold">{{ lesson.subject }}</p>
                  <p class="text-sm text-muted-foreground">{{ lesson.teacher }} · Room {{ lesson.room }}</p>
                </div>
                <Badge variant="outline" class="capitalize shrink-0">
                  {{ lesson.status === 'current' ? 'Live' : lesson.status === 'completed' ? 'Done' : 'Next' }}
                </Badge>
              </li>
            </ol>
          </CardContent>
        </Card>

        <Card class="xl:col-span-5">
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Academic Performance</CardTitle>
              <CardDescription>Subject scores from recorded assessments.</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/student/performance">Full Report</RouterLink>
            </Button>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <p class="text-xs text-muted-foreground">Average</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">
                  {{ hasPerformanceData ? `${averageScore.toFixed(1)}%` : '—' }}
                </p>
              </div>
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <p class="text-xs text-muted-foreground">Attendance</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">
                  {{ hasAttendanceData ? `${attendanceRate.toFixed(1)}%` : '—' }}
                </p>
              </div>
            </div>
            <p v-if="!subjectPerformance.length" class="text-sm text-muted-foreground">
              Marks will appear here once teachers enter them.
            </p>
            <ul v-else class="space-y-2">
              <li
                v-for="row in subjectPerformance"
                :key="row.subject"
                class="flex items-center justify-between gap-3 rounded-xl bg-muted/40 px-3 py-2 ring-1 ring-border/60"
              >
                <span class="truncate text-sm font-medium">{{ row.subject }}</span>
                <span class="tabular-nums text-sm font-semibold">{{ row.percent }}%</span>
              </li>
            </ul>
          </CardContent>
        </Card>
      </section>

      <section class="grid gap-4 lg:grid-cols-2" aria-label="Assignments and exams">
        <Card>
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Assignments &amp; Homework</CardTitle>
              <CardDescription>{{ pendingAssignments }} pending</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/student/assignments">View all</RouterLink>
            </Button>
          </CardHeader>
          <CardContent class="space-y-2">
            <p v-if="!assignmentRows.length" class="text-sm text-muted-foreground">No homework assigned yet.</p>
            <div
              v-for="row in assignmentRows.slice(0, 6)"
              :key="String(row.id)"
              class="flex items-start justify-between gap-3 rounded-xl bg-muted/40 px-3 py-2 ring-1 ring-border/60"
            >
              <div class="min-w-0">
                <p class="truncate font-medium">{{ row.title ?? 'Assignment' }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ assignmentDueLabel(row) }}</p>
              </div>
              <Button
                v-if="canSubmitAssignment(row.submission_status)"
                size="sm"
                class="shrink-0"
                @click="openAssignmentSheet(row)"
              >
                Upload
              </Button>
              <Badge v-else variant="outline" class="shrink-0">{{ submissionLabel(row.submission_status) }}</Badge>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Exam Center</CardTitle>
              <CardDescription>Upcoming papers and latest results.</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/student/exams">Results</RouterLink>
            </Button>
          </CardHeader>
          <CardContent class="space-y-2">
            <p v-if="!examRows.length" class="text-sm text-muted-foreground">No exams published yet.</p>
            <div
              v-for="row in examRows.slice(0, 6)"
              :key="String(row.id ?? row.name)"
              class="flex items-start justify-between gap-3 rounded-xl bg-muted/40 px-3 py-2 ring-1 ring-border/60"
            >
              <div class="min-w-0">
                <p class="truncate font-medium">{{ row.name ?? 'Exam' }}</p>
                <p class="text-xs text-muted-foreground">{{ formatDate(row.exam_date) }}</p>
              </div>
              <Badge variant="outline" class="shrink-0">{{ examResultSummary(row) }}</Badge>
            </div>
          </CardContent>
        </Card>
      </section>

      <section class="grid gap-4 lg:grid-cols-2" aria-label="Attendance and notices">
        <Card>
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Attendance Center</CardTitle>
              <CardDescription>Presence this term.</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/student/attendance">Details</RouterLink>
            </Button>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <p class="text-xs text-muted-foreground">Present</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ attendanceBreakdown.present }}</p>
              </div>
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <p class="text-xs text-muted-foreground">Late</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ attendanceBreakdown.late }}</p>
              </div>
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <p class="text-xs text-muted-foreground">Absent</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ attendanceBreakdown.absent }}</p>
              </div>
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <p class="text-xs text-muted-foreground">Excused</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ attendanceBreakdown.excused }}</p>
              </div>
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <p class="text-xs text-muted-foreground">Days</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ attendanceBreakdown.totalDays }}</p>
              </div>
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <p class="text-xs text-muted-foreground">Rate</p>
                <p class="mt-1 text-xl font-semibold tabular-nums">{{ hasAttendanceData ? `${attendanceRate.toFixed(1)}%` : '—' }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Needs attention</CardTitle>
            <CardDescription>Only items that need a follow-up.</CardDescription>
          </CardHeader>
          <CardContent>
            <ul v-if="attentionItems.length" class="space-y-2" role="list">
              <li v-for="item in attentionItems" :key="item.href + item.title">
                <RouterLink
                  :to="item.href"
                  class="flex items-start justify-between gap-4 rounded-xl bg-muted/40 px-4 py-3 ring-1 ring-border/60 transition-colors hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                  <span class="min-w-0">
                    <span class="block text-sm font-medium">{{ item.title }}</span>
                    <span class="block text-xs text-muted-foreground">{{ item.detail }}</span>
                  </span>
                  <span class="shrink-0 text-xs font-medium text-primary">Open</span>
                </RouterLink>
              </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground" role="status">
              You’re all caught up.
            </p>
          </CardContent>
        </Card>
      </section>
    </template>

    <Card v-if="!error && section === 'performance'" class="border-border/70">
      <CardHeader>
        <CardTitle>Continuous assessment</CardTitle>
        <CardDescription>Subject scores by term (as recorded by teachers)</CardDescription>
      </CardHeader>
      <CardContent class="overflow-x-auto p-0">
        <div v-if="loading" class="p-6 text-sm text-muted-foreground" role="status">Loading performance records…</div>
        <Table v-else-if="performanceRows.length">
          <TableHeader>
            <TableRow>
              <TableHead>Subject</TableHead>
              <TableHead>Assessment</TableHead>
              <TableHead>Term</TableHead>
              <TableHead>Year</TableHead>
              <TableHead>Score</TableHead>
              <TableHead>Grade</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="row in performanceRows" :key="String(row.id ?? `${row.subject}-${row.term}`)">
              <TableCell class="font-medium">{{ row.subject ?? '—' }}</TableCell>
              <TableCell>{{ row.assessment_type ?? 'Assessment' }}</TableCell>
              <TableCell>{{ row.term ?? '—' }}</TableCell>
              <TableCell>{{ row.year ?? '—' }}</TableCell>
              <TableCell class="tabular-nums">
                {{ row.score ?? '—' }}{{ row.total != null ? ` / ${row.total}` : '' }}
              </TableCell>
              <TableCell>
                <Badge variant="outline">{{ row.grade ?? '—' }}</Badge>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <EmptyState v-else variant="embedded" title="No performance records" description="Assessment marks will appear here once teachers enter them." />
      </CardContent>
    </Card>

    <template v-if="!error && section === 'attendance'">
      <section aria-label="Attendance summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Today</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums">{{ hasAttendanceData ? `${attendanceRate.toFixed(0)}%` : '—' }}</p>
          <p class="mt-1 text-xs text-muted-foreground">Attendance rate</p>
        </div>
        <div class="rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Present</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums">{{ attendanceBreakdown.present }}</p>
          <p class="mt-1 text-xs text-muted-foreground">Days marked present</p>
        </div>
        <div class="rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Absentees</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums">{{ attendanceBreakdown.absent }}</p>
          <p class="mt-1 text-xs text-muted-foreground">Days absent</p>
        </div>
        <div class="rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Late</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums">{{ attendanceBreakdown.late }}</p>
          <p class="mt-1 text-xs text-muted-foreground">Late arrivals</p>
        </div>
        <div class="rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">On leave</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums">{{ attendanceBreakdown.excused }}</p>
          <p class="mt-1 text-xs text-muted-foreground">Excused days</p>
        </div>
        <div class="rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Days</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums">{{ attendanceBreakdown.totalDays }}</p>
          <p class="mt-1 text-xs text-muted-foreground">Marked this term</p>
        </div>
      </section>

      <div class="grid gap-4 xl:grid-cols-12">
        <Card class="xl:col-span-5">
          <CardHeader>
            <p class="text-[11px] font-semibold tracking-[0.16em] text-muted-foreground uppercase">Pattern</p>
            <CardTitle class="text-base">30-day heatmap</CardTitle>
            <CardDescription>Each square is a school day on your register.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-if="loading" class="text-sm text-muted-foreground" role="status">Loading attendance…</div>
            <template v-else>
              <div class="grid grid-cols-10 gap-1.5" role="img" aria-label="Attendance heatmap for the last 30 days">
                <span
                  v-for="day in attendanceHeatmap"
                  :key="day.date"
                  class="aspect-square rounded-md"
                  :class="attendanceCellClass(day.status)"
                  :title="`${day.label}: ${day.status || 'not marked'}`"
                />
              </div>
              <ul class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                <li class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-chart-2" aria-hidden="true" /> Present</li>
                <li class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-chart-3" aria-hidden="true" /> Late</li>
                <li class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-destructive/80" aria-hidden="true" /> Absent</li>
                <li class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-primary/70" aria-hidden="true" /> Excused</li>
                <li class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-muted" aria-hidden="true" /> Not marked</li>
              </ul>
            </template>
          </CardContent>
        </Card>

        <Card class="xl:col-span-7">
          <CardHeader>
            <p class="text-[11px] font-semibold tracking-[0.16em] text-muted-foreground uppercase">Workspace</p>
            <CardTitle class="text-base">Attendance Register</CardTitle>
            <CardDescription>Recent days with status, check-in, and remarks.</CardDescription>
          </CardHeader>
          <CardContent class="overflow-x-auto p-0">
            <div v-if="loading" class="p-6 text-sm text-muted-foreground" role="status">Loading register…</div>
            <EmptyState
              v-else-if="!attendanceRows.length"
              variant="embedded"
              title="No attendance marked yet"
              description="Days will appear here once teachers mark the register."
            />
            <Table v-else>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Check-in</TableHead>
                  <TableHead>Remarks</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in attendanceRows.slice(0, 30)" :key="String(row.id ?? row.date)">
                  <TableCell class="font-medium">{{ formatDate(row.date) }}</TableCell>
                  <TableCell>
                    <Badge variant="outline" class="capitalize">{{ String(row.status ?? '—').replace('_', ' ') }}</Badge>
                  </TableCell>
                  <TableCell class="tabular-nums">{{ row.time_in ? formatTime(row.time_in) : '—' }}</TableCell>
                  <TableCell class="max-w-xs truncate text-muted-foreground">{{ row.remarks || '—' }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </template>

    <StudentCbtPanel v-if="!error && section === 'exams'" class="mb-6" />

    <Card v-if="!error && section === 'exams'" class="border-border/70">
      <CardHeader>
        <CardTitle>Examinations</CardTitle>
        <CardDescription>Published exams and your results</CardDescription>
      </CardHeader>
      <CardContent class="overflow-x-auto p-0">
        <div v-if="loading" class="p-6 text-sm text-muted-foreground" role="status">Loading exam records…</div>
        <Table v-else-if="examRows.length">
          <TableHeader>
            <TableRow>
              <TableHead>Exam</TableHead>
              <TableHead>Subject</TableHead>
              <TableHead>Date</TableHead>
              <TableHead>Term</TableHead>
              <TableHead>Result</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="row in examRows" :key="String(row.id ?? row.name)">
              <TableCell class="font-medium">{{ row.name ?? 'Exam' }}</TableCell>
              <TableCell>{{ row.subject ?? '—' }}</TableCell>
              <TableCell>{{ formatDate(row.exam_date) }}</TableCell>
              <TableCell>{{ row.term ?? '—' }}</TableCell>
              <TableCell>{{ examResultSummary(row) }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <EmptyState v-else variant="embedded" title="No published exams" description="Exam results will appear here when they are published." />
      </CardContent>
    </Card>

    <Card v-if="!error && section === 'fees'" class="border-border/70">
      <CardHeader>
        <CardTitle>Fees</CardTitle>
        <CardDescription>Invoice and payment status</CardDescription>
      </CardHeader>
      <CardContent class="overflow-x-auto p-0">
        <div v-if="loading" class="p-6 text-sm text-muted-foreground" role="status">Loading fee records…</div>
        <Table v-else-if="invoiceRows.length">
          <TableHeader>
            <TableRow>
              <TableHead>Description</TableHead>
              <TableHead>Due date</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Balance</TableHead>
              <TableHead>Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="row in invoiceRows" :key="String(row.id ?? row.invoice_number)">
              <TableCell class="font-medium">{{ row.description ?? row.invoice_number ?? 'Invoice' }}</TableCell>
              <TableCell>{{ formatDate(row.due_date) }}</TableCell>
              <TableCell class="tabular-nums">{{ formatMoney(row.amount, String(row.currency ?? 'USD')) }}</TableCell>
              <TableCell class="tabular-nums">{{ formatMoney(row.balance, String(row.currency ?? 'USD')) }}</TableCell>
              <TableCell class="capitalize">{{ row.status ?? 'pending' }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <EmptyState v-else variant="embedded" title="No invoices" description="Fee invoices will appear here when the school issues them." />
      </CardContent>
    </Card>

    <Card v-if="!error && section === 'assignments'" class="border-border/70">
      <CardHeader>
        <CardTitle>Assignments</CardTitle>
        <CardDescription>Open homework and classwork from your teachers</CardDescription>
      </CardHeader>
      <CardContent class="overflow-x-auto p-0">
        <div v-if="loading" class="p-6 text-sm text-muted-foreground" role="status">Loading assignments…</div>
        <Table v-else-if="assignmentRows.length">
          <TableHeader>
            <TableRow>
              <TableHead>Title</TableHead>
              <TableHead>Subject</TableHead>
              <TableHead>Due</TableHead>
              <TableHead>Submission</TableHead>
              <TableHead class="text-right">Score</TableHead>
              <TableHead class="w-[100px]">Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="row in assignmentRows" :key="String(row.id ?? row.title)">
              <TableCell class="font-medium">{{ row.title ?? 'Assignment' }}</TableCell>
              <TableCell>{{ row.subject ?? '—' }}</TableCell>
              <TableCell>
                <span
                  :class="cn(
                    'text-sm',
                    assignmentDueTone(row) === 'danger' && 'font-medium text-destructive',
                    assignmentDueTone(row) === 'warning' && 'font-medium text-chart-3',
                  )"
                >
                  {{ assignmentDueLabel(row) }}
                </span>
              </TableCell>
              <TableCell>
                <Badge variant="outline">{{ submissionLabel(row.submission_status) }}</Badge>
              </TableCell>
              <TableCell class="text-right tabular-nums">
                <span v-if="row.submission_score != null">{{ row.submission_score }}</span>
                <span v-else class="text-muted-foreground">—</span>
              </TableCell>
              <TableCell>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="openAssignmentSheet(row)"
                >
                  {{ canSubmitAssignment(row.submission_status) ? 'Submit' : 'View' }}
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <EmptyState
          v-else
          variant="embedded"
          title="No open assignments"
          description="Homework will appear here when teachers set work for your class."
        />
      </CardContent>
    </Card>

    <section v-if="!error && section === 'announcements'" class="space-y-4" aria-labelledby="student-announcements-title">
      <div class="sr-only">
        <h2 id="student-announcements-title">Announcements</h2>
      </div>
      <div v-if="loading" class="rounded-xl border border-border/70 p-6 text-sm text-muted-foreground" role="status">
        Loading announcements…
      </div>
      <ul v-else-if="announcementRows.length" class="space-y-3" role="list">
        <li v-for="row in announcementRows" :key="String(row.id ?? row.title)">
          <Card class="border-border/70">
            <CardHeader class="space-y-3 pb-3">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 space-y-1">
                  <CardTitle class="text-base">{{ row.title ?? 'Announcement' }}</CardTitle>
                  <CardDescription>
                    {{ formatDate(row.date ?? row.created_at) }}
                    <span aria-hidden="true"> · </span>
                    {{ announcementTypeLabel(row.type) }}
                  </CardDescription>
                </div>
                <Badge variant="secondary">{{ announcementTypeLabel(row.type) }}</Badge>
              </div>
            </CardHeader>
            <CardContent class="space-y-3 pt-0">
              <p
                :class="cn(
                  'text-sm leading-relaxed text-foreground',
                  expandedAnnouncementId === row.id ? 'whitespace-pre-wrap' : 'line-clamp-3',
                )"
              >
                {{ row.message || 'No message provided.' }}
              </p>
              <Button
                v-if="String(row.message ?? '').length > 160"
                type="button"
                variant="ghost"
                size="sm"
                class="h-8 px-2"
                @click="toggleAnnouncement(row.id as number | string)"
              >
                {{ expandedAnnouncementId === row.id ? 'Show less' : 'Read more' }}
              </Button>
            </CardContent>
          </Card>
        </li>
      </ul>
      <Card v-else class="border-border/70">
        <EmptyState
          variant="embedded"
          title="No announcements yet"
          description="School notices for students will show up here."
        />
      </Card>
    </section>

    <Sheet v-model:open="assignmentSheetOpen">
      <SheetContent class="flex w-full flex-col sm:max-w-lg">
        <SheetHeader>
          <SheetTitle>{{ selectedAssignment?.title ?? 'Assignment' }}</SheetTitle>
          <SheetDescription>
            {{ assignmentDueLabel(selectedAssignment ?? {}) }}
            <span v-if="selectedAssignment?.total_marks != null">
              · Max {{ selectedAssignment.total_marks }} marks
            </span>
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 space-y-4 overflow-y-auto py-4">
          <p
            v-if="selectedAssignment?.description"
            class="whitespace-pre-wrap text-sm text-muted-foreground"
          >
            {{ selectedAssignment.description }}
          </p>

          <div
            v-if="selectedAssignment?.submission_status === 'graded' || selectedAssignment?.submission_status === 'returned'"
            class="space-y-2 rounded-md border bg-muted/40 p-3"
            role="status"
          >
            <p class="text-sm font-medium">Teacher feedback</p>
            <p v-if="selectedAssignment.submission_score != null" class="text-sm tabular-nums">
              Score: {{ selectedAssignment.submission_score }}
            </p>
            <p
              v-if="selectedAssignment.teacher_comment"
              class="whitespace-pre-wrap text-sm"
            >
              {{ selectedAssignment.teacher_comment }}
            </p>
            <a
              v-if="selectedAssignment.submission_file_url"
              :href="String(selectedAssignment.submission_file_url)"
              target="_blank"
              rel="noopener noreferrer"
              class="text-sm text-primary underline-offset-4 hover:underline"
            >
              View submitted file
            </a>
          </div>

          <template v-if="canSubmitAssignment(selectedAssignment?.submission_status)">
            <div class="space-y-2">
              <Label for="assignment-content">Your work</Label>
              <Textarea
                id="assignment-content"
                v-model="submitContent"
                rows="6"
                placeholder="Type your answer or notes here…"
                :disabled="submitting"
              />
            </div>
            <div class="space-y-2">
              <Label for="assignment-file">Attachment (optional)</Label>
              <input
                id="assignment-file"
                type="file"
                accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.webp"
                class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-foreground"
                :disabled="submitting"
                @change="onSubmitFileChange"
              />
              <p class="text-xs text-muted-foreground">PDF, Word, or images up to 10 MB.</p>
            </div>
            <p v-if="submitError" class="text-sm text-destructive" role="alert">{{ submitError }}</p>
          </template>
        </div>

        <SheetFooter v-if="canSubmitAssignment(selectedAssignment?.submission_status)" class="gap-2 sm:gap-0">
          <Button
            type="button"
            variant="outline"
            :disabled="submitting"
            @click="assignmentSheetOpen = false"
          >
            Cancel
          </Button>
          <Button type="button" :disabled="submitting" @click="handleSubmitAssignment">
            {{ submitting ? 'Submitting…' : 'Submit assignment' }}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  </div>
</template>
