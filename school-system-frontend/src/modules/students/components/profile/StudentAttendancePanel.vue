<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { CalendarDays, CheckCircle2, Clock3, Plane, XCircle } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import StudentMetricRing from '@/modules/students/components/StudentMetricRing.vue'
import { academicsApi } from '@/services/api.service'
import { formatDate } from '@/lib/format'
import { badgeVariantForStatus, STATUS_TONE_ICON } from '@/lib/ui-status'
import { cn } from '@/lib/utils'

type AttendanceRow = { date?: string; status?: string }

const props = defineProps<{
  studentId: string | number
  recent?: AttendanceRow[]
}>()

const loading = ref(false)
const summary = ref<Record<string, unknown> | null>(null)

const presentRate = computed(() => {
  const fromSummary = Number(summary.value?.present_rate ?? summary.value?.attendance_rate ?? summary.value?.percent)
  if (Number.isFinite(fromSummary) && fromSummary > 0) return Math.round(fromSummary)
  const rows = props.recent ?? []
  if (!rows.length) return 0
  const present = rows.filter((r) => {
    const s = String(r.status ?? '').toLowerCase()
    return s === 'present' || s === 'late' || s === 'half_day'
  }).length
  return Math.round((present / rows.length) * 100)
})

const stats = computed(() => {
  const s = summary.value ?? {}
  const fromSummary = {
    present: Number(s.present ?? s.present_days ?? 0),
    absent: Number(s.absent ?? s.absent_days ?? 0),
    late: Number(s.late ?? s.late_days ?? 0),
    leave: Number(s.on_leave ?? s.excused ?? s.leave ?? 0),
    total: Number(s.total ?? s.school_days ?? 0),
  }
  if (fromSummary.total > 0 || fromSummary.present > 0) return fromSummary

  const rows = props.recent ?? []
  const present = rows.filter((r) => String(r.status ?? '').toLowerCase() === 'present').length
  const absent = rows.filter((r) => String(r.status ?? '').toLowerCase() === 'absent').length
  const late = rows.filter((r) => String(r.status ?? '').toLowerCase() === 'late').length
  const leave = rows.filter((r) => {
    const st = String(r.status ?? '').toLowerCase()
    return st === 'excused' || st === 'on_leave' || st === 'leave'
  }).length
  return { present, absent, late, leave, total: rows.length }
})

const recentRows = computed(() => (props.recent ?? []).slice(0, 20))

async function loadSummary() {
  loading.value = true
  try {
    summary.value = (await academicsApi.attendance.studentSummary(props.studentId)) as Record<string, unknown>
  } catch {
    summary.value = null
  } finally {
    loading.value = false
  }
}

watch(() => props.studentId, loadSummary)
onMounted(loadSummary)
</script>

<template>
  <div class="space-y-6">
    <PageLoader v-if="loading && !recent?.length" label="Loading attendance" />

    <Card>
      <CardContent class="flex flex-col items-center gap-4 p-6 sm:flex-row sm:justify-center sm:gap-10">
        <StudentMetricRing
          label="Present rate"
          :value-label="`${presentRate}%`"
          :percent="presentRate"
          tone="success"
        />
        <div class="space-y-1 text-center sm:text-left">
          <p class="text-sm font-medium text-foreground">
            <CalendarDays class="mr-1 inline size-4 text-muted-foreground" aria-hidden="true" />
            <span class="tabular-nums font-semibold">{{ stats.present }}</span>
            <span class="text-muted-foreground"> / {{ stats.total || '—' }} days attended</span>
          </p>
          <p class="text-xs text-muted-foreground">Based on recorded attendance for this student.</p>
        </div>
      </CardContent>
    </Card>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <Card class="border-l-4 border-l-chart-2">
        <CardContent class="flex items-center gap-3 p-4">
          <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.success)">
            <CheckCircle2 class="size-5" aria-hidden="true" />
          </div>
          <div>
            <p class="text-2xl font-semibold tabular-nums">{{ stats.present }}</p>
            <p class="text-xs text-muted-foreground">Present days</p>
          </div>
        </CardContent>
      </Card>
      <Card class="border-l-4 border-l-destructive">
        <CardContent class="flex items-center gap-3 p-4">
          <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.danger)">
            <XCircle class="size-5" aria-hidden="true" />
          </div>
          <div>
            <p class="text-2xl font-semibold tabular-nums">{{ stats.absent }}</p>
            <p class="text-xs text-muted-foreground">Absent days</p>
          </div>
        </CardContent>
      </Card>
      <Card class="border-l-4 border-l-chart-3">
        <CardContent class="flex items-center gap-3 p-4">
          <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.warning)">
            <Clock3 class="size-5" aria-hidden="true" />
          </div>
          <div>
            <p class="text-2xl font-semibold tabular-nums">{{ stats.late }}</p>
            <p class="text-xs text-muted-foreground">Late arrivals</p>
          </div>
        </CardContent>
      </Card>
      <Card class="border-l-4 border-l-chart-4">
        <CardContent class="flex items-center gap-3 p-4">
          <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.info)">
            <Plane class="size-5" aria-hidden="true" />
          </div>
          <div>
            <p class="text-2xl font-semibold tabular-nums">{{ stats.leave }}</p>
            <p class="text-xs text-muted-foreground">On leave / excused</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="text-base">Recent attendance</CardTitle>
      </CardHeader>
      <CardContent class="p-0">
        <Table v-if="recentRows.length">
          <TableHeader>
            <TableRow>
              <TableHead>Date</TableHead>
              <TableHead>Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(record, i) in recentRows" :key="i">
              <TableCell>{{ formatDate(record.date) }}</TableCell>
              <TableCell>
                <Badge :variant="badgeVariantForStatus(record.status)" class="capitalize">
                  {{ record.status ?? '—' }}
                </Badge>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <div v-else class="p-5">
          <EmptyState
            variant="embedded"
            title="No attendance records"
            description="Marked attendance for this student will appear here."
          />
        </div>
      </CardContent>
    </Card>
  </div>
</template>
