<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { academicsApi, studentsApi } from '@/services/api.service'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const route = useRoute()
const { user } = useAuth()

const loading = ref(false)
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
      return 'Performance'
    case 'attendance':
      return 'Attendance'
    case 'exams':
      return 'Exams'
    case 'fees':
      return 'Fees'
    default:
      return `Welcome, ${studentName.value}`
  }
})

const pageDescription = computed(() => {
  switch (section.value) {
    case 'performance':
      return 'Recent scores and grades'
    case 'attendance':
      return 'Attendance summary and consistency'
    case 'exams':
      return 'Upcoming and completed exams'
    case 'fees':
      return 'Invoice and payment status'
    default:
      return 'Student portal overview'
  }
})

const studentName = computed(() => user.value?.name || 'Student')

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
  const total = performanceRows.value.reduce((sum, row) => sum + Number(row.score ?? 0), 0)
  return total / performanceRows.value.length
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
    dateOfBirth: formatDate(profile.date_of_birth),
    guardian: guardianName || 'Not available',
    phone: String(profile.phone ?? '-'),
    email: String(profile.email ?? user.value?.email ?? '-'),
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

function formatDate(value: unknown): string {
  if (!value) return 'N/A'
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString()
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(value)
}

function examResultSummary(row: Record<string, unknown>): string {
  const result = asRecord(row.result)
  if (!result) return 'Result pending'
  const grade = String(result.grade ?? '-')
  const percentage = result.percentage == null ? '-' : String(result.percentage)
  return `Result: ${grade} (${percentage}%)`
}

function rowKey(row: Record<string, unknown>, index: number, ...fallbackFields: string[]): string {
  for (const field of fallbackFields) {
    const value = row[field]
    if (value != null && value !== '') return `${field}-${String(value)}`
  }
  return `row-${index}`
}

async function loadStudentPortal() {
  if (!studentId.value) {
    error.value = 'Student profile is not linked to this account yet.'
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
  <PageShell
    :title="pageTitle"
    :description="pageDescription"
    :breadcrumbs="['Student portal']"
    max-width="wide"
  >
    <PageLoader v-if="loading && !studentProfile && !error" label="Loading student portal" />

    <ErrorState
      v-else-if="error"
      :description="error"
      @retry="loadStudentPortal"
    />

    <template v-else>
      <div v-if="section === 'dashboard'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Average score</CardTitle>
            <CardDescription>Across recorded assessments</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold">{{ averageScore.toFixed(1) }}%</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Attendance rate</CardTitle>
            <CardDescription>Current attendance summary</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold">{{ attendanceRate.toFixed(1) }}%</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Outstanding balance</CardTitle>
            <CardDescription>Total unpaid amount</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold">{{ formatCurrency(totalOutstanding) }}</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Pending invoices</CardTitle>
            <CardDescription>Unpaid or partial invoices</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold">{{ pendingInvoices }}</CardContent>
        </Card>
      </div>

      <div v-if="section === 'dashboard'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Student number</CardTitle>
            <CardDescription>Official learner identifier</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold">{{ profileView.studentNumber }}</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Current class</CardTitle>
            <CardDescription>Assigned class for this term</CardDescription>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.className }}</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Date of birth</CardTitle>
            <CardDescription>Student profile record</CardDescription>
          </CardHeader>
          <CardContent class="text-2xl font-semibold">{{ profileView.dateOfBirth }}</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Guardian</CardTitle>
            <CardDescription>Primary guardian on file</CardDescription>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.guardian }}</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Contact phone</CardTitle>
            <CardDescription>Student contact information</CardDescription>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.phone }}</CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Contact email</CardTitle>
            <CardDescription>Student profile email</CardDescription>
          </CardHeader>
          <CardContent class="text-lg font-semibold">{{ profileView.email }}</CardContent>
        </Card>
      </div>

      <Card v-if="section === 'dashboard' || section === 'performance'">
        <CardHeader>
          <CardTitle>Performance</CardTitle>
          <CardDescription>Recent scores and grades</CardDescription>
        </CardHeader>
        <CardContent>
          <PageLoader v-if="loading" label="Loading performance records" />
          <EmptyState
            v-else-if="!performanceRows.length"
            title="No performance records"
            description="Grades will appear here once assessments are recorded."
          />
          <div v-else class="space-y-2">
            <div
              v-for="(row, index) in performanceRows.slice(0, 8)"
              :key="rowKey(row, index, 'id', 'subject', 'term')"
              class="rounded-lg border p-3"
            >
              <p class="text-sm font-medium">{{ row.subject ?? 'Subject' }}</p>
              <p class="text-xs text-muted-foreground">{{ row.assessment_type ?? 'Assessment' }} - {{ row.term ?? 'Term' }}</p>
              <p class="mt-1 text-sm">Score: {{ row.score ?? '-' }} / {{ row.total ?? '-' }} ({{ row.grade ?? '-' }})</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="section === 'dashboard' || section === 'attendance'">
        <CardHeader>
          <CardTitle>Attendance</CardTitle>
          <CardDescription>Attendance summary and consistency</CardDescription>
        </CardHeader>
        <CardContent>
          <PageLoader v-if="loading" label="Loading attendance summary" />
          <EmptyState
            v-else-if="!attendanceSummary"
            title="No attendance summary"
            description="Attendance data will show once sessions are recorded."
          />
          <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-lg border p-3">
              <p class="text-xs text-muted-foreground">Present</p>
              <p class="text-lg font-semibold">{{ attendanceBreakdown.present }}</p>
            </div>
            <div class="rounded-lg border p-3">
              <p class="text-xs text-muted-foreground">Late</p>
              <p class="text-lg font-semibold">{{ attendanceBreakdown.late }}</p>
            </div>
            <div class="rounded-lg border p-3">
              <p class="text-xs text-muted-foreground">Absent</p>
              <p class="text-lg font-semibold">{{ attendanceBreakdown.absent }}</p>
            </div>
            <div class="rounded-lg border p-3">
              <p class="text-xs text-muted-foreground">Excused</p>
              <p class="text-lg font-semibold">{{ attendanceBreakdown.excused }}</p>
            </div>
            <div class="rounded-lg border p-3">
              <p class="text-xs text-muted-foreground">Total days</p>
              <p class="text-lg font-semibold">{{ attendanceBreakdown.totalDays }}</p>
            </div>
            <div class="rounded-lg border p-3">
              <p class="text-xs text-muted-foreground">Counted statuses</p>
              <p class="text-lg font-semibold">{{ attendanceBreakdown.counted }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="section === 'dashboard' || section === 'exams'">
        <CardHeader>
          <CardTitle>Exams</CardTitle>
          <CardDescription>Upcoming and completed exams</CardDescription>
        </CardHeader>
        <CardContent>
          <PageLoader v-if="loading" label="Loading exam records" />
          <EmptyState
            v-else-if="!examRows.length"
            title="No exams found"
            description="Scheduled exams and results will appear here."
          />
          <div v-else class="space-y-2">
            <div
              v-for="(row, index) in examRows.slice(0, 8)"
              :key="rowKey(row, index, 'id', 'name', 'exam_date')"
              class="rounded-lg border p-3"
            >
              <p class="text-sm font-medium">{{ row.name ?? 'Exam' }}</p>
              <p class="text-xs text-muted-foreground">{{ row.subject ?? 'Subject' }} - {{ formatDate(row.exam_date) }}</p>
              <p class="mt-1 text-sm">{{ examResultSummary(row) }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="section === 'dashboard' || section === 'fees'">
        <CardHeader>
          <CardTitle>Fees</CardTitle>
          <CardDescription>Invoice and payment status</CardDescription>
        </CardHeader>
        <CardContent>
          <PageLoader v-if="loading" label="Loading fee records" />
          <EmptyState
            v-else-if="!invoiceRows.length"
            title="No invoices found"
            description="Fee invoices will appear here when they are issued."
          />
          <div v-else class="space-y-2">
            <div
              v-for="(row, index) in invoiceRows.slice(0, 8)"
              :key="rowKey(row, index, 'id', 'invoice_number')"
              class="rounded-lg border p-3"
            >
              <p class="text-sm font-medium">{{ row.description ?? 'Invoice' }}</p>
              <p class="text-xs text-muted-foreground">Due: {{ formatDate(row.due_date) }} - Status: {{ String(row.status ?? 'pending').toUpperCase() }}</p>
              <p class="mt-1 text-sm">Amount: {{ formatCurrency(Number(row.amount ?? 0)) }} | Balance: {{ formatCurrency(Number(row.balance ?? 0)) }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </template>
  </PageShell>
</template>
