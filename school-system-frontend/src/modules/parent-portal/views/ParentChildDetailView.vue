<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { Download, FileSpreadsheet } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { parentPortalApi, studentsApi } from '@/services/index'
import { formatMoney } from '@/lib/finance-constants'
import { formatDate, formatDateTime } from '@/lib/format'
import { getErrorMessage } from '@/lib/api-response'
import { useToast } from '@/composables/useToast'

const route = useRoute()
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

const childName = computed(() =>
  String(child.value?.full_name ?? child.value?.fullName ?? (child.value?.student_number ? `Student ${child.value.student_number}` : 'Student')),
)

const childDescription = computed(() => {
  const classLabel = child.value?.class ? `Class: ${child.value.class}` : 'Child profile'
  const number = child.value?.student_number ? ` · ${child.value.student_number}` : ''
  return `${classLabel}${number}`
})

const overallAverage = computed(() => {
  const bySubject = progress.value?.by_subject as Array<{ average_percent?: number }> | undefined
  if (Array.isArray(bySubject) && bySubject.length) {
    const total = bySubject.reduce((sum, row) => sum + Number(row.average_percent ?? 0), 0)
    return `${(total / bySubject.length).toFixed(1)}%`
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

const outstandingBalance = computed(() =>
  Number(fees.value?.outstanding_balance ?? fees.value?.balance ?? child.value?.balance ?? 0),
)

function asRecordArray(value: unknown): Array<Record<string, unknown>> {
  if (!Array.isArray(value)) return []
  return value.filter(
    (item): item is Record<string, unknown> => !!item && typeof item === 'object' && !Array.isArray(item),
  )
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
        <RouterLink to="/portal/children">Back to children</RouterLink>
      </Button>
    </template>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Outstanding balance</CardDescription>
            <CardTitle class="text-xl tabular-nums">
              {{ formatMoney(outstandingBalance, String(fees?.currency ?? child?.currency ?? 'USD')) }}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Overall average</CardDescription>
            <CardTitle class="text-xl tabular-nums">{{ overallAverage }}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Absences recorded</CardDescription>
            <CardTitle class="text-xl tabular-nums">
              {{ attendanceSummary.absent ?? attendance.filter((a) => a.status === 'absent').length }}
            </CardTitle>
          </CardHeader>
        </Card>
      </div>

      <Tabs default-value="results">
        <TabsList class="grid w-full grid-cols-2 sm:grid-cols-4">
          <TabsTrigger value="results">Results</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="fees">Fees</TabsTrigger>
          <TabsTrigger value="discipline">Discipline</TabsTrigger>
        </TabsList>

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
