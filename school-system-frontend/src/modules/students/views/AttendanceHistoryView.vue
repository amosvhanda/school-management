<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { CalendarDays, CheckCircle2, Download, Printer, RefreshCw } from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter'
import { academicsApi, studentsApi } from '@/services/api.service'
import { cn } from '@/lib/utils'
import { STATUS_TONE_BADGE, STATUS_TONE_DOT } from '@/lib/ui-status'

type StudentOpt = { id: number; full_name?: string; first_name?: string; last_name?: string; student_number?: string; class_id?: number }
type AttRecord = { date?: string; status?: string; check_in_at?: string | null }

const route = useRoute()
const { academicYear } = useAcademicYearFilter()

const students = ref<StudentOpt[]>([])
const studentId = ref(typeof route.query.student === 'string' ? route.query.student : '')
const month = ref(new Date().toISOString().slice(0, 7))
const loading = ref(false)
const summary = ref<Record<string, unknown> | null>(null)

const records = computed(() => (summary.value?.records as AttRecord[] | undefined) ?? [])
const present = computed(() => Number(summary.value?.present ?? 0))
const absent = computed(() => Number(summary.value?.absent ?? 0))
const late = computed(() => Number(summary.value?.late ?? 0))
const leave = computed(() => Number(summary.value?.excused ?? summary.value?.on_leave ?? 0))
const total = computed(() => Number(summary.value?.total_days ?? records.value.length))
const presentRate = computed(() => {
  const r = Number(summary.value?.present_rate ?? summary.value?.attendance_rate ?? 0)
  return Math.round(r * 10) / 10
})

const calendarDays = computed(() => {
  const [y, m] = month.value.split('-').map(Number)
  if (!y || !m) return [] as Array<{ day: number; status?: string }>
  const daysInMonth = new Date(y, m, 0).getDate()
  const byDate = new Map(records.value.map((r) => [String(r.date).slice(0, 10), String(r.status ?? '').toLowerCase()]))
  return Array.from({ length: daysInMonth }, (_, i) => {
    const day = i + 1
    const key = `${y}-${String(m).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    return { day, status: byDate.get(key) }
  })
})

function statusDot(status?: string) {
  if (!status) return 'bg-muted'
  if (status === 'present') return STATUS_TONE_DOT.success
  if (status === 'absent') return STATUS_TONE_DOT.danger
  if (status === 'late') return STATUS_TONE_DOT.warning
  return STATUS_TONE_DOT.info
}

function studentLabel(s: StudentOpt) {
  return s.full_name || [s.first_name, s.last_name].filter(Boolean).join(' ') || `Student #${s.id}`
}

async function loadStudents() {
  try {
    students.value = (await studentsApi.list({ per_page: 200 } as never)) as StudentOpt[]
    if (!studentId.value && students.value[0]) studentId.value = String(students.value[0].id)
  } catch {
    students.value = []
  }
}

async function loadSummary() {
  if (!studentId.value) {
    summary.value = null
    return
  }
  loading.value = true
  try {
    summary.value = (await academicsApi.attendance.studentSummary(studentId.value, {
      month: month.value,
    } as never)) as Record<string, unknown>
  } catch {
    summary.value = null
  } finally {
    loading.value = false
  }
}

function printPage() {
  window.print()
}

watch([studentId, month], loadSummary)
onMounted(async () => {
  await loadStudents()
  await loadSummary()
})
</script>

<template>
  <PageShell
    title="Attendance history"
    description="Daily attendance records across classes and terms."
    :breadcrumbs="['Students', 'Attendance history']"
    max-width="wide"
  >
    <template #actions>
      <Button type="button" variant="outline" size="icon" class="rounded-xl" aria-label="Refresh" @click="loadSummary">
        <RefreshCw class="size-4" />
      </Button>
      <Button type="button" variant="outline" class="rounded-xl" @click="printPage">
        <Printer class="mr-1.5 size-4" />
        Print
      </Button>
      <Button type="button" variant="outline" class="rounded-xl" disabled>
        <Download class="mr-1.5 size-4" />
        Export
      </Button>
    </template>

    <Card class="mb-6">
      <CardContent class="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-1.5">
          <Label>Student</Label>
          <Select v-model="studentId">
            <SelectTrigger class="rounded-xl"><SelectValue placeholder="Select student" /></SelectTrigger>
            <SelectContent>
              <SelectItem v-for="s in students" :key="s.id" :value="String(s.id)">
                {{ studentLabel(s) }}
                <span v-if="s.student_number" class="text-muted-foreground"> · {{ s.student_number }}</span>
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1.5">
          <Label>Academic Year</Label>
          <p class="flex h-9 items-center rounded-xl border border-input px-3 text-sm">{{ academicYear }}</p>
        </div>
        <div class="space-y-1.5 sm:col-span-2 lg:col-span-1">
          <Label for="att-month">Month</Label>
          <input
            id="att-month"
            v-model="month"
            type="month"
            class="flex h-9 w-full rounded-xl border border-input bg-background px-3 text-sm"
          />
        </div>
      </CardContent>
    </Card>

    <PageLoader v-if="loading" label="Loading attendance" />
    <template v-else>
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <KpiCard title="Present" :value="String(present)" hint="Days present" />
        <KpiCard title="Monthly" :value="`${present} / ${total || '—'}`" hint="Days" />
        <KpiCard title="Present %" :value="`${presentRate}%`" />
        <KpiCard title="Absent %" :value="`${total ? Math.round((absent / total) * 1000) / 10 : 0}%`" />
        <KpiCard title="Late" :value="String(late)" hint="Month" />
        <KpiCard title="Leave" :value="String(leave)" hint="Excused" />
      </div>

      <div
        v-if="total > 0"
        :class="cn(
          'mb-6 rounded-2xl border px-4 py-3 text-sm',
          presentRate >= 75 ? STATUS_TONE_BADGE.success : STATUS_TONE_BADGE.warning,
        )"
      >
        <CheckCircle2 v-if="presentRate >= 75" class="mr-1.5 inline size-4" />
        {{ presentRate >= 75
          ? 'Attendance is above the required 75% threshold for this academic year.'
          : 'Attendance is below the 75% threshold — review absences.' }}
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <Card class="lg:col-span-1">
          <CardHeader class="pb-2">
            <CardTitle class="flex items-center gap-2 text-base">
              <CalendarDays class="size-4" />
              Monthly calendar
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="calendarDays.length" class="grid grid-cols-7 gap-1.5">
              <div
                v-for="cell in calendarDays"
                :key="cell.day"
                class="flex flex-col items-center gap-1 rounded-lg border border-border/50 py-1.5 text-xs"
              >
                <span>{{ cell.day }}</span>
                <span class="size-2 rounded-full" :class="statusDot(cell.status)" aria-hidden="true" />
              </div>
            </div>
            <EmptyState v-else variant="embedded" title="No records" description="Pick a student and month." />
            <div class="mt-3 flex flex-wrap gap-3 text-[10px] text-muted-foreground">
              <span class="inline-flex items-center gap-1"><span :class="cn('size-2 rounded-full', STATUS_TONE_DOT.success)" />Present</span>
              <span class="inline-flex items-center gap-1"><span :class="cn('size-2 rounded-full', STATUS_TONE_DOT.danger)" />Absent</span>
              <span class="inline-flex items-center gap-1"><span :class="cn('size-2 rounded-full', STATUS_TONE_DOT.warning)" />Late</span>
              <span class="inline-flex items-center gap-1"><span :class="cn('size-2 rounded-full', STATUS_TONE_DOT.info)" />Leave</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2"><CardTitle class="text-base">Distribution</CardTitle></CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p>Present · {{ present }}</p>
            <p>Absent · {{ absent }}</p>
            <p>Late · {{ late }}</p>
            <p>Leave · {{ leave }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2"><CardTitle class="text-base">This month</CardTitle></CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p class="font-semibold text-chart-2">Present: {{ present }}</p>
            <p class="font-semibold text-destructive">Absent: {{ absent }}</p>
            <p class="font-semibold text-chart-3">Late: {{ late }}</p>
            <p class="font-semibold text-chart-4">Leave: {{ leave }}</p>
          </CardContent>
        </Card>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle class="text-base">Attendance Trend</CardTitle></CardHeader>
          <CardContent>
            <EmptyState variant="embedded" title="Trend chart" description="Needs more historical months to plot." />
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle class="text-base">Heatmap</CardTitle></CardHeader>
          <CardContent>
            <EmptyState variant="embedded" title="Heatmap" description="Use the calendar above for daily status." />
          </CardContent>
        </Card>
      </div>
    </template>
  </PageShell>
</template>
