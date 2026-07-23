<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { BarChart3, BookOpen, ClipboardCheck, FileText } from '@lucide/vue'
import AttendancePanel from '@/components/dashboard/AttendancePanel.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Progress } from '@/components/ui/progress'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { formatDateTime } from '@/lib/format'
import { useRouteAccess } from '@/composables/useRouteAccess'
import type { AttendanceSummary } from '@/types/dashboard'
import {
  ACADEMICS_PERIOD_OPTIONS,
  attendanceRate,
  rateAccent,
  type AcademicReport,
  type AcademicsPeriod,
  type AttendanceReport,
  type ExamAnalyticsReport,
} from '@/modules/analytics/types/academics-analytics'

const props = defineProps<{
  loading?: boolean
  attendance: AttendanceReport | null
  academic: AcademicReport | null
  examAnalytics: ExamAnalyticsReport | null
  todaySummary?: AttendanceSummary | null
}>()

const period = defineModel<AcademicsPeriod>('period', { required: true })

const { canOpen } = useRouteAccess()
const canOpenAttendance = computed(() => canOpen('/academics/attendance'))
const canOpenGradebook = computed(() => canOpen('/academics/grades'))
const canOpenExams = computed(() => canOpen('/academics/exams'))

const periodLabel = computed(
  () => ACADEMICS_PERIOD_OPTIONS.find((o) => o.value === period.value)?.label ?? 'Period',
)

const attendanceByClass = computed(() =>
  [...(props.attendance?.by_class ?? [])]
    .sort((a, b) => attendanceRate(b) - attendanceRate(a))
    .slice(0, 10),
)

const subjectPerformance = computed(() =>
  [...(props.academic?.by_subject ?? [])]
    .sort((a, b) => Number(b.average_percent ?? 0) - Number(a.average_percent ?? 0))
    .slice(0, 8),
)

const examBySubject = computed(() =>
  [...(props.examAnalytics?.by_subject ?? [])]
    .sort((a, b) => Number(b.average_percent ?? 0) - Number(a.average_percent ?? 0))
    .slice(0, 8),
)

const examByExam = computed(() =>
  [...(props.examAnalytics?.by_exam ?? [])]
    .sort((a, b) => Number(b.average_percent ?? 0) - Number(a.average_percent ?? 0))
    .slice(0, 10),
)

const statusSegments = computed(() => {
  const byStatus = props.attendance?.by_status ?? {}
  return [
    { key: 'present', label: 'Present', value: Number(byStatus.present ?? 0), color: 'bg-emerald-500' },
    { key: 'absent', label: 'Absent', value: Number(byStatus.absent ?? 0), color: 'bg-red-500' },
    { key: 'late', label: 'Late', value: Number(byStatus.late ?? 0), color: 'bg-amber-500' },
    { key: 'excused', label: 'Excused', value: Number(byStatus.excused ?? 0), color: 'bg-blue-500' },
  ]
})

const statusTotal = computed(() =>
  statusSegments.value.reduce((sum, seg) => sum + seg.value, 0),
)

const generatedAt = computed(() => {
  const stamp = props.attendance?.generated_at ?? props.academic?.generated_at
  return stamp ? formatDateTime(stamp) : null
})

function onPeriodChange(value: unknown) {
  if (typeof value === 'string' && ACADEMICS_PERIOD_OPTIONS.some((o) => o.value === value)) {
    period.value = value as AcademicsPeriod
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h3 class="text-sm font-semibold tracking-tight">Detailed analytics</h3>
        <p class="text-xs text-muted-foreground">
          Attendance trends, gradebook averages, and exam performance
          <span v-if="generatedAt"> · Updated {{ generatedAt }}</span>
        </p>
      </div>
      <div class="w-full max-w-xs space-y-2">
        <Label for="academics-period">Reporting period</Label>
        <Select :model-value="period" @update:model-value="onPeriodChange">
          <SelectTrigger id="academics-period" class="w-full">
            <SelectValue :placeholder="periodLabel" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="option in ACADEMICS_PERIOD_OPTIONS"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <PageLoader v-if="loading" label="Loading analytics" />

    <template v-else>
      <AttendancePanel
        v-if="todaySummary"
        :summary="todaySummary"
      />

      <Card>
        <CardHeader class="border-b border-border/60 pb-4">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <CardTitle class="text-base font-semibold">Attendance overview</CardTitle>
              <CardDescription>{{ periodLabel }} — {{ attendance?.total_records ?? 0 }} records</CardDescription>
            </div>
            <Button v-if="canOpenAttendance" variant="outline" size="sm" as-child>
              <RouterLink to="/academics/attendance">
                <ClipboardCheck class="mr-2 h-4 w-4" aria-hidden="true" />
                Open register
              </RouterLink>
            </Button>
          </div>
        </CardHeader>
        <CardContent class="space-y-6 pt-6">
          <template v-if="statusTotal > 0">
            <div class="flex h-3 overflow-hidden rounded-full bg-muted" role="img" :aria-label="`Attendance breakdown for ${periodLabel}`">
              <div
                v-for="seg in statusSegments.filter((s) => s.value > 0)"
                :key="seg.key"
                :class="seg.color"
                :style="{ width: `${(seg.value / statusTotal) * 100}%` }"
                :title="`${seg.label}: ${seg.value}`"
              />
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div v-for="seg in statusSegments" :key="seg.key" class="rounded-lg border p-3">
                <div class="flex items-center gap-2">
                  <span :class="['size-2 rounded-full', seg.color]" aria-hidden="true" />
                  <span class="text-xs text-muted-foreground">{{ seg.label }}</span>
                </div>
                <p class="mt-1 text-xl font-semibold">{{ seg.value }}</p>
              </div>
            </div>
          </template>

          <div v-if="attendanceByClass.length" class="space-y-4">
            <h4 class="text-sm font-medium">By class</h4>
            <ul class="space-y-3">
              <li
                v-for="row in attendanceByClass"
                :key="row.class_id ?? row.class_name"
                class="space-y-1.5"
              >
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                  <span class="font-medium">{{ row.class_name ?? 'Class' }}</span>
                  <div class="flex items-center gap-2">
                    <span class="text-muted-foreground">
                      {{ row.present ?? 0 }}/{{ row.total ?? 0 }} present
                    </span>
                    <Badge
                      :variant="rateAccent(attendanceRate(row)) === 'success' ? 'default' : 'secondary'"
                    >
                      {{ attendanceRate(row) }}%
                    </Badge>
                  </div>
                </div>
                <Progress :model-value="attendanceRate(row)" />
              </li>
            </ul>
          </div>

          <EmptyState
            v-else
            title="No attendance in this period"
            description="Mark attendance from the register or widen the reporting period."
          >
            <Button v-if="canOpenAttendance" as-child class="mt-2">
              <RouterLink to="/academics/attendance">Take attendance</RouterLink>
            </Button>
          </EmptyState>
        </CardContent>
      </Card>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader class="border-b border-border/60 pb-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <CardTitle class="text-base font-semibold">Gradebook by subject</CardTitle>
                <CardDescription>{{ academic?.total_records ?? 0 }} mark records</CardDescription>
              </div>
              <Button v-if="canOpenGradebook" variant="outline" size="sm" as-child>
                <RouterLink to="/academics/grades">
                  <BookOpen class="mr-2 h-4 w-4" aria-hidden="true" />
                  Gradebook
                </RouterLink>
              </Button>
            </div>
          </CardHeader>
          <CardContent class="pt-6">
            <ul v-if="subjectPerformance.length" class="space-y-3">
              <li
                v-for="row in subjectPerformance"
                :key="row.subject ?? 'unknown'"
                class="space-y-1.5"
              >
                <div class="flex justify-between text-sm">
                  <span>{{ row.subject ?? 'Subject' }}</span>
                  <span class="font-medium">{{ row.average_percent ?? 0 }}%</span>
                </div>
                <Progress :model-value="Number(row.average_percent ?? 0)" />
              </li>
            </ul>
            <EmptyState
              v-else
              title="No gradebook data"
              description="Enter marks in the gradebook to see subject averages here."
            >
              <Button v-if="canOpenGradebook" as-child class="mt-2" variant="outline">
                <RouterLink to="/academics/grades">Open gradebook</RouterLink>
              </Button>
            </EmptyState>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="border-b border-border/60 pb-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <CardTitle class="text-base font-semibold">Exams by subject</CardTitle>
                <CardDescription>{{ examAnalytics?.total_records ?? 0 }} exam results</CardDescription>
              </div>
              <Button v-if="canOpenExams" variant="outline" size="sm" as-child>
                <RouterLink to="/academics/exams">
                  <FileText class="mr-2 h-4 w-4" aria-hidden="true" />
                  Examinations
                </RouterLink>
              </Button>
            </div>
          </CardHeader>
          <CardContent class="pt-6">
            <ul v-if="examBySubject.length" class="space-y-3">
              <li
                v-for="row in examBySubject"
                :key="row.subject ?? 'unknown'"
                class="space-y-1.5"
              >
                <div class="flex justify-between text-sm">
                  <span>{{ row.subject ?? 'Subject' }}</span>
                  <span class="font-medium">{{ row.average_percent ?? 0 }}%</span>
                </div>
                <Progress :model-value="Number(row.average_percent ?? 0)" />
              </li>
            </ul>
            <EmptyState
              v-else
              title="No exam results yet"
              description="Schedule exams and enter marks to track performance by subject."
            >
              <Button v-if="canOpenExams" as-child class="mt-2" variant="outline">
                <RouterLink to="/academics/exams">View examinations</RouterLink>
              </Button>
            </EmptyState>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="border-b border-border/60 pb-4">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <CardTitle class="text-base font-semibold">Exam performance</CardTitle>
              <CardDescription>Average scores across scheduled examinations</CardDescription>
            </div>
            <BarChart3 class="h-5 w-5 text-muted-foreground" aria-hidden="true" />
          </div>
        </CardHeader>
        <CardContent class="pt-6">
          <div v-if="examByExam.length" class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Exam</TableHead>
                  <TableHead>Year</TableHead>
                  <TableHead class="text-right">Students</TableHead>
                  <TableHead class="text-right">Average</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in examByExam" :key="row.exam_id ?? row.exam_name">
                  <TableCell class="font-medium">{{ row.exam_name ?? '—' }}</TableCell>
                  <TableCell>{{ row.academic_year ?? '—' }}</TableCell>
                  <TableCell class="text-right">{{ row.students ?? 0 }}</TableCell>
                  <TableCell class="text-right">
                    <Badge variant="outline">{{ row.average_percent ?? 0 }}%</Badge>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <EmptyState
            v-else
            title="No exam analytics"
            description="Results appear here once teachers enter marks for examinations."
          />
        </CardContent>
      </Card>
    </template>
  </div>
</template>
