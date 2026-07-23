<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { Download, FileSpreadsheet } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { parentPortalApi, studentsApi } from '@/services/index'
import { formatMoney } from '@/lib/finance-constants'
import { formatDate, formatDateTime } from '@/lib/format'
import { getErrorMessage } from '@/lib/api-response'
import { useToast } from '@/composables/useToast'
import { cn } from '@/lib/utils'

type DetailTab = 'progress' | 'results' | 'attendance' | 'fees' | 'discipline'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const studentId = computed(() => String(route.params.id))

const loading = ref(true)
const downloading = ref(false)
const error = ref<string | null>(null)
const child = ref<Record<string, unknown> | null>(null)
const reportCardGrades = ref<Record<string, unknown>[]>([])
const examResults = ref<Record<string, unknown>[]>([])
const attendance = ref<Record<string, unknown>[]>([])
const attendanceSummary = ref<Record<string, number>>({})
const fees = ref<Record<string, unknown> | null>(null)
const discipline = ref<Record<string, unknown>[]>([])
const progress = ref<Record<string, unknown> | null>(null)

const TAB_IDS: DetailTab[] = ['progress', 'results', 'attendance', 'fees', 'discipline']

function tabFromQuery(value: unknown): DetailTab {
  const raw = String(Array.isArray(value) ? value[0] : value ?? '')
  return TAB_IDS.includes(raw as DetailTab) ? (raw as DetailTab) : 'progress'
}

const activeTab = ref<DetailTab>(tabFromQuery(route.query.tab))

const childName = computed(() =>
  String(child.value?.full_name ?? child.value?.fullName ?? (child.value?.student_number ? `Student ${child.value.student_number}` : 'Student')),
)

const childDescription = computed(() => {
  const classLabel = child.value?.class ? `Class: ${child.value.class}` : 'Child profile'
  const number = child.value?.student_number ? ` · ${child.value.student_number}` : ''
  return `${classLabel}${number}`
})

const subjectProgress = computed(() => {
  const rows = progress.value?.by_subject as Array<{ subject?: string; average_percent?: number; records?: number }> | undefined
  if (!Array.isArray(rows)) return []
  return rows
    .map((row) => ({
      subject: String(row.subject ?? 'Subject'),
      average_percent: Number(row.average_percent ?? 0),
      records: Number(row.records ?? 0),
    }))
    .filter((row) => Number.isFinite(row.average_percent))
    .sort((a, b) => b.average_percent - a.average_percent)
})

const recentGrades = computed(() => {
  const rows = progress.value?.recent_grades as Array<Record<string, unknown>> | undefined
  return Array.isArray(rows) ? rows : []
})

const overallAverage = computed(() => {
  if (subjectProgress.value.length) {
    const total = subjectProgress.value.reduce((sum, row) => sum + row.average_percent, 0)
    return `${(total / subjectProgress.value.length).toFixed(1)}%`
  }
  if (reportCardGrades.value.length) {
    const scored = reportCardGrades.value.filter((row) => Number(row.total) > 0)
    if (!scored.length) return '—'
    const total = scored.reduce(
      (sum, row) => sum + (Number(row.score ?? 0) / Number(row.total)) * 100,
      0,
    )
    return `${(total / scored.length).toFixed(1)}%`
  }
  return '—'
})

const attendanceRate = computed(() => {
  const present = Number(attendanceSummary.value.present ?? 0)
  const absent = Number(attendanceSummary.value.absent ?? 0)
  const late = Number(attendanceSummary.value.late ?? 0)
  const total = present + absent + late
  if (!total) {
    const fromRecords = attendance.value.length
    if (!fromRecords) return null
    const presentCount = attendance.value.filter((row) => String(row.status) === 'present').length
    return Math.round((presentCount / fromRecords) * 100)
  }
  return Math.round((present / total) * 100)
})

const outstandingBalance = computed(() =>
  Number(fees.value?.outstanding_balance ?? fees.value?.balance ?? child.value?.balance ?? 0),
)

function asRecordArray(value: unknown): Array<Record<string, unknown>> {
  if (!Array.isArray(value)) return []
  return value.filter(
    (item): item is Record<string, unknown> => !!item && typeof item === 'object' && !Array.isArray(item),
  )
}

function subjectTone(percent: number) {
  if (percent >= 70) return 'text-emerald-700 dark:text-emerald-400'
  if (percent >= 50) return 'text-amber-700 dark:text-amber-400'
  return 'text-destructive'
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
  downloading.value = true
  try {
    const blob = await studentsApi.downloadResults(studentId.value, format)
    const number = String(child.value?.student_number ?? 'student')
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

function setTab(tab: string | number) {
  const next = tabFromQuery(tab)
  activeTab.value = next
  const query = { ...route.query }
  if (next === 'progress') delete query.tab
  else query.tab = next
  void router.replace({ query })
}

watch(
  () => route.query.tab,
  (value) => {
    activeTab.value = tabFromQuery(value)
  },
)

async function load() {
  loading.value = true
  error.value = null
  try {
    const children = (await parentPortalApi.children()) as Record<string, unknown>[]
    child.value = children.find((c) => String(c.id) === studentId.value) ?? null

    const [resultsData, attendanceData, feesData, disciplineData, progressData] = await Promise.all([
      parentPortalApi.results(studentId.value).catch(() => null),
      parentPortalApi.attendance(studentId.value).catch(() => null),
      parentPortalApi.fees(studentId.value).catch(() => null),
      parentPortalApi.discipline(studentId.value).catch(() => null),
      parentPortalApi.progress(studentId.value).catch(() => null),
    ])

    reportCardGrades.value = asRecordArray(resultsData?.report_card_grades)
    examResults.value = asRecordArray(resultsData?.exam_results)
    attendance.value = asRecordArray(attendanceData?.records)
    attendanceSummary.value = (attendanceData?.summary as Record<string, number> | undefined) ?? {}
    fees.value = feesData as Record<string, unknown> | null
    discipline.value = asRecordArray(disciplineData?.records)
    progress.value = progressData as Record<string, unknown> | null

    if (resultsData?.student && !child.value) {
      child.value = resultsData.student as Record<string, unknown>
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load child details'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    :title="childName"
    :description="childDescription"
    max-width="wide"
  >
    <template #actions>
      <Button variant="outline" :disabled="downloading || loading" @click="downloadResults('html')">
        <Download class="mr-2 size-4" aria-hidden="true" />
        Download report card
      </Button>
      <Button variant="outline" :disabled="downloading || loading" @click="downloadResults('csv')">
        <FileSpreadsheet class="mr-2 size-4" aria-hidden="true" />
        Download CSV
      </Button>
      <Button variant="outline" as-child>
        <RouterLink to="/portal">Back to home</RouterLink>
      </Button>
    </template>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Overall average</CardDescription>
            <CardTitle class="text-2xl tabular-nums">{{ overallAverage }}</CardTitle>
          </CardHeader>
          <CardContent class="pt-0 text-xs text-muted-foreground">
            Across {{ subjectProgress.length || 'published' }} subjects
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Attendance</CardDescription>
            <CardTitle class="text-2xl tabular-nums">
              {{ attendanceRate != null ? `${attendanceRate}%` : '—' }}
            </CardTitle>
          </CardHeader>
          <CardContent class="pt-0 text-xs text-muted-foreground">
            {{ attendanceSummary.absent ?? attendance.filter((a) => a.status === 'absent').length }} absences recorded
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Outstanding fees</CardDescription>
            <CardTitle class="text-2xl tabular-nums">
              {{ formatMoney(outstandingBalance, String(fees?.currency ?? child?.currency ?? 'USD')) }}
            </CardTitle>
          </CardHeader>
          <CardContent class="pt-0 text-xs text-muted-foreground">
            Open the Fees tab for invoices and payments
          </CardContent>
        </Card>
      </div>

      <Tabs :model-value="activeTab" @update:model-value="setTab">
        <TabsList class="grid h-auto w-full grid-cols-2 gap-1 sm:grid-cols-5">
          <TabsTrigger value="progress">Progress</TabsTrigger>
          <TabsTrigger value="results">Results</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="fees">Fees</TabsTrigger>
          <TabsTrigger value="discipline">Discipline</TabsTrigger>
        </TabsList>

        <TabsContent value="progress" class="mt-4 space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>How {{ childName.split(' ')[0] }} is doing</CardTitle>
              <CardDescription>
                Subject averages from continuous assessment — higher bars mean stronger performance.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="subjectProgress.length" class="space-y-4">
                <div
                  v-for="row in subjectProgress"
                  :key="row.subject"
                  class="space-y-2 rounded-xl border border-border/60 p-4"
                >
                  <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                      <p class="truncate font-medium">{{ row.subject }}</p>
                      <p class="text-xs text-muted-foreground">
                        {{ row.records }} {{ row.records === 1 ? 'mark' : 'marks' }} recorded
                      </p>
                    </div>
                    <p :class="cn('text-lg font-semibold tabular-nums', subjectTone(row.average_percent))">
                      {{ row.average_percent.toFixed(1) }}%
                    </p>
                  </div>
                  <Progress
                    :model-value="Math.min(100, Math.max(0, row.average_percent))"
                    class="h-2.5"
                    :aria-label="`${row.subject} average ${row.average_percent}%`"
                  />
                </div>
              </div>
              <p v-else class="text-sm text-muted-foreground">
                No subject progress has been published yet. Check back after teachers enter marks.
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent marks</CardTitle>
              <CardDescription>Latest continuous assessment scores</CardDescription>
            </CardHeader>
            <CardContent class="overflow-x-auto p-0">
              <Table v-if="recentGrades.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Subject</TableHead>
                    <TableHead>Score</TableHead>
                    <TableHead>Grade</TableHead>
                    <TableHead>Term</TableHead>
                    <TableHead>Year</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow
                    v-for="(row, index) in recentGrades"
                    :key="String(row.id ?? `${row.subject}-${row.term}-${index}`)"
                  >
                    <TableCell class="font-medium">{{ row.subject ?? '—' }}</TableCell>
                    <TableCell class="tabular-nums">
                      {{ row.score ?? '—' }}{{ row.total != null ? ` / ${row.total}` : '' }}
                    </TableCell>
                    <TableCell><Badge variant="outline">{{ row.grade ?? '—' }}</Badge></TableCell>
                    <TableCell>{{ row.term ?? '—' }}</TableCell>
                    <TableCell>{{ row.year ?? '—' }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="p-6 text-sm text-muted-foreground">No recent marks to show.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="results" class="mt-4 space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Continuous assessment</CardTitle>
              <CardDescription>Report-card grades by subject and term</CardDescription>
            </CardHeader>
            <CardContent class="overflow-x-auto p-0">
              <Table v-if="reportCardGrades.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Subject</TableHead>
                    <TableHead>Score</TableHead>
                    <TableHead>Grade</TableHead>
                    <TableHead>Term</TableHead>
                    <TableHead>Year</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="row in reportCardGrades" :key="String(row.id ?? `${row.subject}-${row.term}`)">
                    <TableCell class="font-medium">{{ row.subject ?? '—' }}</TableCell>
                    <TableCell class="tabular-nums">
                      {{ row.score ?? '—' }}{{ row.total != null ? ` / ${row.total}` : '' }}
                    </TableCell>
                    <TableCell><Badge variant="outline">{{ row.grade ?? '—' }}</Badge></TableCell>
                    <TableCell>{{ row.term ?? '—' }}</TableCell>
                    <TableCell>{{ row.year ?? '—' }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="p-6 text-sm text-muted-foreground">No report-card grades published yet.</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Examination results</CardTitle>
              <CardDescription>Published exam marks only</CardDescription>
            </CardHeader>
            <CardContent class="overflow-x-auto p-0">
              <Table v-if="examResults.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Exam</TableHead>
                    <TableHead>Subject</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Marks</TableHead>
                    <TableHead>Grade</TableHead>
                    <TableHead>Term / Year</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="row in examResults" :key="String(row.id)">
                    <TableCell class="font-medium">{{ row.exam_name ?? 'Exam' }}</TableCell>
                    <TableCell>{{ row.subject ?? '—' }}</TableCell>
                    <TableCell>{{ formatDate(row.exam_date) }}</TableCell>
                    <TableCell class="tabular-nums">
                      {{ row.marks_obtained ?? '—' }}
                      <span v-if="row.total_marks != null"> / {{ row.total_marks }}</span>
                      <span v-if="row.percentage != null" class="text-muted-foreground">
                        ({{ row.percentage }}%)
                      </span>
                    </TableCell>
                    <TableCell><Badge variant="outline">{{ row.grade ?? '—' }}</Badge></TableCell>
                    <TableCell>
                      {{ row.term ?? '—' }}{{ row.academic_year ? ` · ${row.academic_year}` : '' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="p-6 text-sm text-muted-foreground">No published exam results yet.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="attendance" class="mt-4">
          <Card>
            <CardHeader><CardTitle>Attendance history</CardTitle></CardHeader>
            <CardContent class="overflow-x-auto p-0">
              <Table v-if="attendance.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Class / Subject</TableHead>
                    <TableHead>Notes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="row in attendance" :key="String(row.id ?? row.date)">
                    <TableCell>{{ formatDate(row.date ?? row.attendance_date) }}</TableCell>
                    <TableCell class="capitalize">{{ row.status ?? '—' }}</TableCell>
                    <TableCell>{{ row.class ?? row.subject ?? '—' }}</TableCell>
                    <TableCell>{{ row.remarks ?? row.notes ?? row.reason ?? '—' }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="p-6 text-sm text-muted-foreground">No attendance records.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="fees" class="mt-4">
          <Card>
            <CardHeader>
              <CardTitle>Fees & invoices</CardTitle>
              <CardDescription>
                Outstanding:
                {{ formatMoney(outstandingBalance, String(fees?.currency ?? child?.currency ?? 'USD')) }}
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6 overflow-x-auto">
              <Table v-if="Array.isArray(fees?.invoices) && fees.invoices.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Invoice</TableHead>
                    <TableHead>Due date</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Balance</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="inv in (fees.invoices as Record<string, unknown>[])" :key="String(inv.id)">
                    <TableCell>{{ inv.invoice_number ?? inv.description ?? '—' }}</TableCell>
                    <TableCell>{{ formatDate(inv.due_date) }}</TableCell>
                    <TableCell class="tabular-nums">{{ formatMoney(inv.amount, String(inv.currency ?? 'USD')) }}</TableCell>
                    <TableCell class="tabular-nums">{{ formatMoney(inv.balance, String(inv.currency ?? 'USD')) }}</TableCell>
                    <TableCell class="capitalize">{{ inv.status ?? '—' }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="text-sm text-muted-foreground">No invoice records.</p>

              <div v-if="Array.isArray(fees?.payments) && fees.payments.length">
                <h3 class="mb-2 text-sm font-medium">Recent payments</h3>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date</TableHead>
                      <TableHead>Amount</TableHead>
                      <TableHead>Method</TableHead>
                      <TableHead>Reference</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="pay in (fees.payments as Record<string, unknown>[])" :key="String(pay.id)">
                      <TableCell>{{ formatDate(pay.date) }}</TableCell>
                      <TableCell class="tabular-nums">{{ formatMoney(pay.amount, String(pay.currency ?? 'USD')) }}</TableCell>
                      <TableCell class="capitalize">{{ pay.method ?? '—' }}</TableCell>
                      <TableCell>{{ pay.reference ?? '—' }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="discipline" class="mt-4">
          <Card>
            <CardHeader><CardTitle>Discipline records</CardTitle></CardHeader>
            <CardContent>
              <ul v-if="discipline.length" class="space-y-3">
                <li v-for="row in discipline" :key="String(row.id)" class="rounded-lg border p-4">
                  <p class="font-medium capitalize">{{ row.category ?? row.incident_type ?? row.type ?? 'Incident' }}</p>
                  <p class="text-sm text-muted-foreground">
                    {{ formatDate(row.incident_date ?? row.date) }}
                    <span v-if="row.severity"> · {{ row.severity }}</span>
                    <span v-if="row.parent_notified_at">
                      · Parent notified {{ formatDateTime(row.parent_notified_at) }}
                    </span>
                  </p>
                  <p v-if="row.description" class="mt-2 text-sm">{{ row.description }}</p>
                  <p v-if="row.action_taken" class="mt-1 text-sm text-muted-foreground">
                    Action: {{ row.action_taken }}
                  </p>
                </li>
              </ul>
              <p v-else class="text-sm text-muted-foreground">No discipline records.</p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </template>
  </PageShell>
</template>
