<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Download, FileSpreadsheet } from '@lucide/vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { academicsApi, studentsApi } from '@/services/api.service'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { formatMoney } from '@/lib/finance-constants'
import { STUDENT_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
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
      return `Welcome, ${user.value?.name?.split(' ')[0] || 'Student'}`
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
  if (!studentId.value) {
    error.value = 'Student profile is not linked to this account yet. Please contact the school office.'
    return
  }

  loading.value = true
  error.value = null

  const [profile, attendance, grades, exams, invoices] = await Promise.allSettled([
    studentsApi.get(studentId.value),
    academicsApi.attendance.studentSummary(studentId.value),
    academicsApi.grades.byStudent(studentId.value),
    studentsApi.exams(studentId.value),
    studentsApi.invoices(studentId.value),
  ])

  if (profile.status === 'fulfilled') {
    studentProfile.value = asRecord(profile.value)
  }
  if (attendance.status === 'fulfilled') {
    attendanceSummary.value = asRecord(attendance.value)
  }
  if (grades.status === 'fulfilled') {
    performanceRows.value = asRecordArray(grades.value)
  }
  if (exams.status === 'fulfilled') {
    examRows.value = asRecordArray(exams.value)
  }
  if (invoices.status === 'fulfilled') {
    invoiceRows.value = asRecordArray(invoices.value)
  }

  const failures = [profile, attendance, grades, exams, invoices].filter((r) => r.status === 'rejected').length
  if (failures > 0 && !performanceRows.value.length && !examRows.value.length && !invoiceRows.value.length) {
    error.value = 'Could not load student records at the moment.'
  }

  loading.value = false
}

onMounted(loadStudentPortal)
</script>

<template>
  <div class="mx-auto max-w-6xl space-y-6 pb-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ pageTitle }}</h1>
        <p class="text-sm text-muted-foreground">{{ pageDescription }}</p>
      </div>
      <div
        v-if="studentId && (section === 'dashboard' || section === 'performance' || section === 'exams')"
        class="flex flex-wrap gap-2"
      >
        <Button
          variant="outline"
          :disabled="downloading || loading"
          @click="downloadResults('html')"
        >
          <Download class="mr-2 size-4" aria-hidden="true" />
          Download report card
        </Button>
        <Button
          variant="outline"
          :disabled="downloading || loading"
          @click="downloadResults('csv')"
        >
          <FileSpreadsheet class="mr-2 size-4" aria-hidden="true" />
          Download CSV
        </Button>
      </div>
    </div>

    <Alert v-if="error" variant="destructive">
      <AlertDescription>{{ error }}</AlertDescription>
    </Alert>

    <template v-if="section === 'dashboard'">
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Average score</CardTitle>
            <CardDescription>Across recorded assessments</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold tabular-nums">{{ averageScore.toFixed(1) }}%</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Attendance rate</CardTitle>
            <CardDescription>Current attendance summary</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold tabular-nums">{{ attendanceRate.toFixed(1) }}%</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Outstanding balance</CardTitle>
            <CardDescription>Total unpaid amount</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold tabular-nums">{{ formatMoney(totalOutstanding) }}</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Pending invoices</CardTitle>
            <CardDescription>Unpaid or partial invoices</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold tabular-nums">{{ pendingInvoices }}</CardContent>
        </Card>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Student number</CardTitle>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.studentNumber }}</CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Current class</CardTitle>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.className }}</CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Date of birth</CardTitle>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.dateOfBirth }}</CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Guardian</CardTitle>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.guardian }}</CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Contact phone</CardTitle>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.phone }}</CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Contact email</CardTitle>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.email }}</CardContent>
        </Card>
      </div>

      <DashboardModulesGrid
        :groups="STUDENT_DASHBOARD_MODULE_GROUPS"
        title="My modules"
        description="Jump to performance, attendance, exams, and fees"
      />
    </template>

    <Card v-if="section === 'dashboard' || section === 'performance'">
      <CardHeader>
        <CardTitle>Continuous assessment</CardTitle>
        <CardDescription>Subject scores by term (as recorded by teachers)</CardDescription>
      </CardHeader>
      <CardContent class="overflow-x-auto p-0">
        <div v-if="loading" class="p-6 text-sm text-muted-foreground">Loading performance records…</div>
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
        <p v-else class="p-6 text-sm text-muted-foreground">No performance records found yet.</p>
      </CardContent>
    </Card>

    <Card v-if="section === 'dashboard' || section === 'attendance'">
      <CardHeader>
        <CardTitle>Attendance</CardTitle>
        <CardDescription>Summary of your presence this term</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="loading" class="text-sm text-muted-foreground">Loading attendance summary…</div>
        <div v-else-if="!attendanceSummary" class="text-sm text-muted-foreground">No attendance summary found.</div>
        <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <div class="rounded-lg border p-3">
            <p class="text-xs text-muted-foreground">Present</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.present }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-muted-foreground">Late</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.late }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-muted-foreground">Absent</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.absent }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-muted-foreground">Excused</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.excused }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-muted-foreground">Total days</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceBreakdown.totalDays }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-muted-foreground">Attendance rate</p>
            <p class="text-lg font-semibold tabular-nums">{{ attendanceRate.toFixed(1) }}%</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card v-if="section === 'dashboard' || section === 'exams'">
      <CardHeader>
        <CardTitle>Examinations</CardTitle>
        <CardDescription>Published exams and your results</CardDescription>
      </CardHeader>
      <CardContent class="overflow-x-auto p-0">
        <div v-if="loading" class="p-6 text-sm text-muted-foreground">Loading exam records…</div>
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
        <p v-else class="p-6 text-sm text-muted-foreground">No published exams found yet.</p>
      </CardContent>
    </Card>

    <Card v-if="section === 'dashboard' || section === 'fees'">
      <CardHeader>
        <CardTitle>Fees</CardTitle>
        <CardDescription>Invoice and payment status</CardDescription>
      </CardHeader>
      <CardContent class="overflow-x-auto p-0">
        <div v-if="loading" class="p-6 text-sm text-muted-foreground">Loading fee records…</div>
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
        <p v-else class="p-6 text-sm text-muted-foreground">No invoices found.</p>
      </CardContent>
    </Card>
  </div>
</template>
