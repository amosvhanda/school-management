<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  ClipboardCheck,
  Download,
  FileSpreadsheet,
  FileText,
  Receipt,
  TrendingUp,
} from '@lucide/vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { studentPortalApi, studentsApi } from '@/services/api.service'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { formatMoney } from '@/lib/finance-constants'
import { STUDENT_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const route = useRoute()
const { user } = useAuth()
const toast = useToast()

const loading = ref(false)
const downloading = ref(false)
const error = ref<string | null>(null)
const lastUpdated = ref<Date | null>(null)

const studentProfile = ref<Record<string, unknown> | null>(null)
const attendanceSummary = ref<Record<string, unknown> | null>(null)
const performanceRows = ref<Array<Record<string, unknown>>>([])
const examRows = ref<Array<Record<string, unknown>>>([])
const invoiceRows = ref<Array<Record<string, unknown>>>([])

const section = computed(() => {
  if (route.path === '/student/performance') return 'performance'
  if (route.path === '/student/attendance') return 'attendance'
  if (route.path === '/student/exams') return 'exams'
  if (route.path === '/student/fees') return 'fees'
  return 'dashboard'
})

const pageTitle = computed(() => {
  switch (section.value) {
    case 'performance':
      return 'My performance'
    case 'attendance':
      return 'My attendance'
    case 'exams':
      return 'My exams'
    case 'fees':
      return 'My fees'
    default:
      return 'Student dashboard'
  }
})

const pageDescription = computed(() => {
  switch (section.value) {
    case 'performance':
      return 'Your continuous assessment marks by subject and term.'
    case 'attendance':
      return 'Presence summary for the current term.'
    case 'exams':
      return 'Published examination timetable and results.'
    case 'fees':
      return 'Invoices and outstanding balances.'
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

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'Average score',
    value: `${averageScore.value.toFixed(1)}%`,
    subtitle: 'Across recorded assessments',
    icon: TrendingUp,
    href: '/student/performance',
  },
  {
    title: 'Attendance rate',
    value: `${attendanceRate.value.toFixed(1)}%`,
    subtitle: 'Current attendance summary',
    icon: ClipboardCheck,
    accent: attendanceRate.value < 80 ? 'warning' : 'success',
    href: '/student/attendance',
  },
  {
    title: 'Outstanding balance',
    value: formatMoney(totalOutstanding.value),
    subtitle: 'Total unpaid amount',
    icon: Receipt,
    accent: totalOutstanding.value > 0 ? 'danger' : undefined,
    href: '/student/fees',
  },
  {
    title: 'Pending invoices',
    value: pendingInvoices.value,
    subtitle: 'Unpaid or partial invoices',
    icon: FileText,
    accent: pendingInvoices.value > 0 ? 'warning' : undefined,
    href: '/student/fees',
  },
])

const profileView = computed(() => {
  const profile = studentProfile.value ?? {}
  const guardianFirst = String(profile.guardian_first_name ?? '').trim()
  const guardianLast = String(profile.guardian_last_name ?? '').trim()
  const guardianName = `${guardianFirst} ${guardianLast}`.trim()

  return {
    studentNumber: String(profile.student_number ?? 'Not assigned'),
    className: String(profile.class ?? 'Not assigned'),
    dateOfBirth: formatDate(profile.date_of_birth, 'Not on file'),
    guardian: guardianName || 'Not available',
    phone: String(profile.phone ?? '—'),
    email: String(profile.email ?? user.value?.email ?? '—'),
  }
})

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

    const [gradesPayload, feesPayload, attendancePayload] = await Promise.allSettled([
      studentPortalApi.grades(),
      studentPortalApi.fees(),
      studentPortalApi.attendance(),
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
      if (summary) {
        attendanceSummary.value = summary
      } else if (!attendanceSummary.value) {
        const rows = asRecordArray(data?.rows ?? attendancePayload.value)
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
  <div class="mx-auto w-full max-w-[1600px] space-y-8 pb-8">
    <DashboardHero
      v-if="section === 'dashboard'"
      :name="user?.name"
      role="Student"
      subtitle="Your school records at a glance — performance, attendance, exams, and fees."
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="refresh"
    >
      <template v-if="studentId" #actions>
        <Button
          variant="outline"
          size="sm"
          class="h-9"
          :disabled="downloading || loading"
          @click="downloadResults('html')"
        >
          <Download class="mr-2 size-4" aria-hidden="true" />
          Report card
        </Button>
        <Button
          variant="outline"
          size="sm"
          class="h-9"
          :disabled="downloading || loading"
          @click="downloadResults('csv')"
        >
          <FileSpreadsheet class="mr-2 size-4" aria-hidden="true" />
          CSV
        </Button>
      </template>
    </DashboardHero>

    <header
      v-else
      class="flex flex-col gap-4 border-b border-border/60 pb-6 sm:flex-row sm:items-end sm:justify-between"
    >
      <div class="min-w-0 space-y-1.5">
        <h1 class="font-heading text-2xl font-semibold tracking-tight text-foreground md:text-[1.75rem]">
          {{ pageTitle }}
        </h1>
        <p class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
          {{ pageDescription }}
        </p>
      </div>
      <div
        v-if="studentId && (section === 'performance' || section === 'exams')"
        class="flex flex-wrap gap-2"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="downloading || loading"
          @click="downloadResults('html')"
        >
          <Download class="mr-2 size-4" aria-hidden="true" />
          Download report card
        </Button>
        <Button
          variant="outline"
          size="sm"
          :disabled="downloading || loading"
          @click="downloadResults('csv')"
        >
          <FileSpreadsheet class="mr-2 size-4" aria-hidden="true" />
          Download CSV
        </Button>
      </div>
    </header>

    <Alert v-if="error" variant="destructive">
      <AlertDescription>{{ error }}</AlertDescription>
    </Alert>

    <template v-if="section === 'dashboard'">
      <MetricBand
        title="My overview"
        description="Performance, attendance, and fees at a glance"
        :cards="overviewCards"
        skip-permission-filter
      />

      <section class="space-y-3" aria-labelledby="student-profile-title">
        <div>
          <h2 id="student-profile-title" class="text-base font-semibold tracking-tight md:text-lg">
            Profile
          </h2>
          <p class="text-sm text-muted-foreground">Your enrolment and contact details</p>
        </div>
        <Card class="border-border/70">
          <CardContent class="p-0">
            <dl class="grid gap-0 sm:grid-cols-2 lg:grid-cols-3">
              <div class="space-y-1 border-b border-border/60 px-5 py-4 sm:border-r lg:border-b">
                <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Student number</dt>
                <dd class="text-sm font-semibold text-foreground">{{ profileView.studentNumber }}</dd>
              </div>
              <div class="space-y-1 border-b border-border/60 px-5 py-4 lg:border-r">
                <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Current class</dt>
                <dd class="text-sm font-semibold text-foreground">{{ profileView.className }}</dd>
              </div>
              <div class="space-y-1 border-b border-border/60 px-5 py-4 sm:border-r lg:border-r-0">
                <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Date of birth</dt>
                <dd class="text-sm font-semibold text-foreground">{{ profileView.dateOfBirth }}</dd>
              </div>
              <div class="space-y-1 border-b border-border/60 px-5 py-4 lg:border-b-0 lg:border-r">
                <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Guardian</dt>
                <dd class="text-sm font-semibold text-foreground">{{ profileView.guardian }}</dd>
              </div>
              <div class="space-y-1 border-b border-border/60 px-5 py-4 sm:border-r sm:border-b-0 lg:border-b-0">
                <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Contact phone</dt>
                <dd class="text-sm font-semibold text-foreground">{{ profileView.phone }}</dd>
              </div>
              <div class="space-y-1 px-5 py-4">
                <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Contact email</dt>
                <dd class="text-sm font-semibold text-foreground break-all">{{ profileView.email }}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>
      </section>

      <DashboardModulesGrid
        :groups="STUDENT_DASHBOARD_MODULE_GROUPS"
        title="Quick actions"
        description="Open timetable, performance, attendance, exams, or fees"
        skip-permission-filter
        :show-search="false"
      />
    </template>

    <Card v-if="section === 'performance'" class="border-border/70">
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

    <Card v-if="section === 'attendance'" class="border-border/70">
      <CardHeader>
        <CardTitle>Attendance</CardTitle>
        <CardDescription>Summary of your presence this term</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="loading" class="text-sm text-muted-foreground" role="status">Loading attendance summary…</div>
        <div v-else-if="!attendanceSummary" class="text-sm text-muted-foreground">No attendance summary found.</div>
        <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <div class="rounded-lg border border-border/70 p-3">
            <p class="text-xs text-muted-foreground">Present</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.present }}</p>
          </div>
          <div class="rounded-lg border border-border/70 p-3">
            <p class="text-xs text-muted-foreground">Late</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.late }}</p>
          </div>
          <div class="rounded-lg border border-border/70 p-3">
            <p class="text-xs text-muted-foreground">Absent</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.absent }}</p>
          </div>
          <div class="rounded-lg border border-border/70 p-3">
            <p class="text-xs text-muted-foreground">Excused</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.excused }}</p>
          </div>
          <div class="rounded-lg border border-border/70 p-3">
            <p class="text-xs text-muted-foreground">Total days</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.totalDays }}</p>
          </div>
          <div class="rounded-lg border border-border/70 p-3">
            <p class="text-xs text-muted-foreground">Attendance rate</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceRate.toFixed(1) }}%</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card v-if="section === 'exams'" class="border-border/70">
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

    <Card v-if="section === 'fees'" class="border-border/70">
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
  </div>
</template>
