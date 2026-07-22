<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  AlertTriangle,
  BookOpen,
  CalendarDays,
  Check,
  CheckCircle2,
  Clock,
  Save,
  Search,
  UserX,
  Users,
  X,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import DatePicker from '@/components/forms/DatePicker.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { toast } from 'vue-sonner'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate, formatDateTime } from '@/lib/format'
import { cn } from '@/lib/utils'
import { academicsApi, complianceApi, reportsApi } from '@/services/api.service'
import { fetchAnalyticsInsights, fetchList } from '@/services/dashboard.service'
import { moduleEndpoints } from '@/services'
import type { ListQueryParams } from '@/types/api'
import type { AttendanceReport } from '@/modules/analytics/types/academics-analytics'

type AttendanceStatus = 'present' | 'absent' | 'late' | 'excused'
type MarkStatus = AttendanceStatus | 'unmarked'
type SortKey = 'name' | 'id' | 'status'
type StatusFilter = 'all' | MarkStatus

interface ClassOption {
  id: number
  name: string
  form?: string | null
}

interface StudentOption {
  id: number
  full_name?: string
  student_number?: string
}

interface RegisterRow {
  student_id: number
  full_name: string
  student_number: string
  status: MarkStatus
  time_in: string
  remarks: string
}

interface AttendanceRecord {
  id: number
  student_id: number
  status: AttendanceStatus
  date?: string
  time_in?: string | null
  remarks?: string | null
  student?: { full_name?: string; student_number?: string }
  class_model?: { name?: string }
}

interface AtRiskStudent {
  id: number
  full_name?: string
  student_number?: string
  class_id?: number
  balance?: number
}

interface AuditRow {
  id: number
  action?: string
  description?: string
  created_at?: string
  user?: { name?: string }
}

const MARK_OPTIONS: {
  value: MarkStatus
  label: string
  icon: typeof Check
  activeClass: string
}[] = [
  {
    value: 'present',
    label: 'Present',
    icon: Check,
    activeClass: 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-600/90',
  },
  {
    value: 'late',
    label: 'Late',
    icon: Clock,
    activeClass: 'border-amber-500 bg-amber-500 text-black hover:bg-amber-500/90',
  },
  {
    value: 'excused',
    label: 'Excused',
    icon: BookOpen,
    activeClass: 'border-blue-600 bg-blue-600 text-white hover:bg-blue-600/90',
  },
  {
    value: 'absent',
    label: 'Absent',
    icon: X,
    activeClass: 'border-destructive bg-destructive text-destructive-foreground hover:bg-destructive/90',
  },
  {
    value: 'unmarked',
    label: 'Unmarked',
    icon: Users,
    activeClass: 'border-foreground bg-foreground text-background hover:bg-foreground/90',
  },
]

const route = useRoute()
const { user } = useAuth()
const loading = ref(true)
const registerLoading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const classes = ref<ClassOption[]>([])
const selectedClassId = ref('')
const registerDate = ref(new Date().toISOString().slice(0, 10))
const todayIso = computed(() => new Date().toISOString().slice(0, 10))
const sessionValue = ref('full-day')
const sortBy = ref<SortKey>('name')
const searchQuery = ref('')
const statusFilter = ref<StatusFilter>('all')
const registerRows = ref<RegisterRow[]>([])
const historyRows = ref<AttendanceRecord[]>([])
const historyLoading = ref(false)
const activeTab = ref('mark')
const registerDirty = ref(false)
const atRisk = ref<AtRiskStudent[]>([])
const report = ref<AttendanceReport | null>(null)
const reportLoading = ref(false)
const classAnalytics = ref<Record<string, unknown> | null>(null)
const analyticsLoading = ref(false)
const auditRows = ref<AuditRow[]>([])
const auditLoading = ref(false)

const selectedClass = computed(() =>
  classes.value.find((c) => String(c.id) === selectedClassId.value),
)

const stats = computed(() => {
  const total = registerRows.value.length
  const present = registerRows.value.filter((r) => r.status === 'present').length
  const absent = registerRows.value.filter((r) => r.status === 'absent').length
  const late = registerRows.value.filter((r) => r.status === 'late').length
  const excused = registerRows.value.filter((r) => r.status === 'excused').length
  const unmarked = registerRows.value.filter((r) => r.status === 'unmarked').length
  const marked = present + absent + late + excused
  const pct = (n: number) => (total > 0 ? Math.round((n / total) * 100) : 0)

  return {
    total,
    present,
    absent,
    late,
    excused,
    unmarked,
    marked,
    presentPct: pct(present),
    absentPct: pct(absent),
    latePct: pct(late),
    excusedPct: pct(excused),
    progressPct: pct(marked),
  }
})

const filteredRows = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  let rows = [...registerRows.value]

  if (q) {
    rows = rows.filter(
      (r) =>
        r.full_name.toLowerCase().includes(q) ||
        r.student_number.toLowerCase().includes(q),
    )
  }

  if (statusFilter.value !== 'all') {
    rows = rows.filter((r) => r.status === statusFilter.value)
  }

  rows.sort((a, b) => {
    if (sortBy.value === 'id') {
      return a.student_number.localeCompare(b.student_number, undefined, { numeric: true })
    }
    if (sortBy.value === 'status') {
      return a.status.localeCompare(b.status) || a.full_name.localeCompare(b.full_name)
    }
    return a.full_name.localeCompare(b.full_name)
  })

  return rows
})

const classAtRisk = computed(() => {
  if (!selectedClassId.value) return atRisk.value
  const classId = Number(selectedClassId.value)
  return atRisk.value.filter((s) => s.class_id == null || s.class_id === classId)
})

const registerComplete = computed(
  () => registerRows.value.length > 0 && stats.value.unmarked === 0,
)

function parseTimeIn(value: unknown) {
  if (!value) return ''
  const str = String(value)
  const match = str.match(/(\d{2}:\d{2})/)
  return match?.[1] ?? ''
}

function markButtonClass(opt: (typeof MARK_OPTIONS)[number], active: boolean) {
  return cn(
    'h-9 gap-1.5 px-3 text-xs font-medium transition-colors',
    active
      ? opt.activeClass
      : 'border-border bg-background text-muted-foreground hover:bg-muted/50',
  )
}

function statusBadgeVariant(status: AttendanceStatus) {
  switch (status) {
    case 'present':
      return 'default' as const
    case 'absent':
      return 'destructive' as const
    case 'late':
      return 'outline' as const
    default:
      return 'secondary' as const
  }
}

async function loadClasses() {
  loading.value = true
  error.value = null
  try {
    const preferredClassId = route.query.class_id ? String(route.query.class_id) : ''

    if (user.value?.role === 'teacher' && user.value.teacher_id) {
      const teacherId = user.value.teacher_id
      const [assignments, formClasses] = await Promise.all([
        academicsApi.teacherAssignments.list({ teacher_id: teacherId }) as Promise<
          Array<{ class_id?: number | null; class_model?: { id?: number; name?: string; form?: string | null } | null }>
        >,
        academicsApi.classes.list({ teacher_id: teacherId }) as Promise<ClassOption[]>,
      ])

      const byId = new Map<number, ClassOption>()
      for (const cls of formClasses) {
        byId.set(cls.id, cls)
      }
      for (const a of assignments) {
        const id = Number(a.class_id ?? a.class_model?.id)
        if (!Number.isFinite(id) || byId.has(id)) continue
        byId.set(id, {
          id,
          name: a.class_model?.name || `Class ${id}`,
          form: a.class_model?.form ?? null,
        })
      }
      classes.value = [...byId.values()].sort((a, b) => a.name.localeCompare(b.name))
    } else {
      classes.value = (await academicsApi.classes.list()) as ClassOption[]
    }

    if (preferredClassId && classes.value.some((c) => String(c.id) === preferredClassId)) {
      selectedClassId.value = preferredClassId
    } else if (classes.value.length && !selectedClassId.value) {
      selectedClassId.value = String(classes.value[0].id)
    } else if (
      selectedClassId.value &&
      !classes.value.some((c) => String(c.id) === selectedClassId.value)
    ) {
      selectedClassId.value = classes.value[0] ? String(classes.value[0].id) : ''
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load classes')
  } finally {
    loading.value = false
  }
}

async function loadAtRisk() {
  try {
    const data = (await fetchAnalyticsInsights()) as { students_at_risk?: AtRiskStudent[] }
    atRisk.value = data.students_at_risk ?? []
  } catch {
    atRisk.value = []
  }
}

async function loadRegister() {
  if (!selectedClassId.value) {
    registerRows.value = []
    return
  }

  registerLoading.value = true
  try {
    const [students, existing] = await Promise.all([
      fetchList<StudentOption>(moduleEndpoints.students, {
        filter: { class_id: selectedClassId.value },
        limit: 500,
        include: 'classModel',
      }),
      academicsApi.attendance.list({
        filter: {
          class_id: selectedClassId.value,
          date: registerDate.value,
        },
        all: true,
        include: 'student,classModel',
      }) as Promise<AttendanceRecord[]>,
    ])

    const byStudent = new Map<number, AttendanceRecord>()
    for (const row of existing) {
      byStudent.set(row.student_id, row)
    }

    registerRows.value = students.map((student) => {
      const saved = byStudent.get(student.id)
      return {
        student_id: student.id,
        full_name:
          student.full_name ??
          (student.student_number ? `Student ${student.student_number}` : 'Student'),
        student_number: student.student_number ?? '—',
        status: saved?.status ?? 'unmarked',
        time_in: parseTimeIn(saved?.time_in),
        remarks: saved?.remarks ?? '',
      }
    })

    registerDirty.value = false
  } catch (err) {
    toast.error('Could not load register', { description: getErrorMessage(err) })
    registerRows.value = []
  } finally {
    registerLoading.value = false
  }
}

async function loadHistory() {
  historyLoading.value = true
  try {
    const params: ListQueryParams = {
      all: true,
      include: 'student,classModel',
      sort: '-date',
      filter: {},
    }
    if (selectedClassId.value) {
      params.filter = { ...params.filter, class_id: selectedClassId.value }
    }
    historyRows.value = (await academicsApi.attendance.list(params)) as AttendanceRecord[]
  } catch {
    historyRows.value = []
  } finally {
    historyLoading.value = false
  }
}

async function loadReport() {
  reportLoading.value = true
  try {
    const to = registerDate.value
    const fromDate = new Date(to)
    fromDate.setDate(fromDate.getDate() - 30)
    const from = fromDate.toISOString().slice(0, 10)
    report.value = (await reportsApi.attendance({
      from,
      to,
      ...(selectedClassId.value ? { class_id: selectedClassId.value } : {}),
    })) as AttendanceReport
  } catch {
    report.value = null
  } finally {
    reportLoading.value = false
  }
}

async function loadAnalytics() {
  if (!selectedClassId.value) {
    classAnalytics.value = null
    return
  }
  analyticsLoading.value = true
  try {
    classAnalytics.value = (await academicsApi.attendance.classReport(
      selectedClassId.value,
    )) as Record<string, unknown>
  } catch {
    classAnalytics.value = null
  } finally {
    analyticsLoading.value = false
  }
}

async function loadAudit() {
  auditLoading.value = true
  try {
    auditRows.value = (await complianceApi.auditLogs.list({
      module: 'attendance',
      limit: 50,
      sort: '-created_at',
    })) as AuditRow[]
  } catch {
    auditRows.value = []
  } finally {
    auditLoading.value = false
  }
}

function setStatus(row: RegisterRow, status: MarkStatus) {
  row.status = status
  if (status !== 'late') row.time_in = ''
  registerDirty.value = true
}

function markAllPresent() {
  for (const row of registerRows.value) {
    row.status = 'present'
    row.time_in = ''
  }
  registerDirty.value = true
}

async function saveRegister() {
  if (!selectedClassId.value || !registerRows.value.length) return

  const marked = registerRows.value.filter((r) => r.status !== 'unmarked')
  if (!marked.length) {
    toast.warning('Nothing to save', { description: 'Mark at least one learner first.' })
    return
  }
  if (marked.length < registerRows.value.length) {
    toast.warning('Incomplete register', {
      description: `${registerRows.value.length - marked.length} learner(s) still unmarked. Mark everyone before saving.`,
    })
    return
  }

  saving.value = true
  try {
    await academicsApi.attendance.record({
      class_id: Number(selectedClassId.value),
      date: registerDate.value,
      overwrite: true,
      records: marked.map((row) => ({
        student_id: row.student_id,
        status: row.status,
        remarks: row.remarks.trim() || null,
        time: row.status === 'late' && row.time_in ? row.time_in : null,
      })),
    })
    registerDirty.value = false
    toast.success('Attendance register saved')
    await Promise.all([loadRegister(), loadHistory(), loadAtRisk()])
  } catch (err) {
    toast.error('Save failed', { description: getErrorMessage(err) })
  } finally {
    saving.value = false
  }
}

watch([selectedClassId, registerDate], () => {
  void loadRegister()
})

watch(activeTab, (tab) => {
  if (tab === 'history') void loadHistory()
  if (tab === 'reports') void loadReport()
  if (tab === 'analytics') void loadAnalytics()
  if (tab === 'audit') void loadAudit()
})

onMounted(async () => {
  await Promise.all([loadClasses(), loadAtRisk()])
  await loadRegister()
})
</script>

<template>
  <PageShell
    title="Attendance"
    description="Mark daily class attendance, review history, and track learners at risk."
    max-width="wide"
  >
    <template #actions>
      <Button variant="outline" :disabled="!registerRows.length" @click="markAllPresent">
        Mark all present
      </Button>
      <Button
        :disabled="saving || !registerRows.length"
        :aria-busy="saving"
        @click="saveRegister"
      >
        <Save class="mr-2 size-4" aria-hidden="true" />
        {{ saving ? 'Saving…' : 'Save register' }}
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading attendance workspace…" />
    <ErrorState v-else-if="error" :description="error" @retry="loadClasses" />

    <div v-else class="space-y-6">
      <div
        v-if="classAtRisk.length"
        class="flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
        role="status"
      >
        <div class="flex items-start gap-2">
          <AlertTriangle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
          <p>
            <span class="font-medium">{{ classAtRisk.length }} student(s) at risk</span>
            — low attendance or consecutive absences detected.
          </p>
        </div>
        <Button
          variant="link"
          class="h-auto p-0 text-amber-900 underline-offset-2 dark:text-amber-100"
          @click="activeTab = 'analytics'"
        >
          View Analytics →
        </Button>
      </div>

      <Card class="border-border/70 shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="text-base">Filters &amp; Controls</CardTitle>
          <CardDescription>Choose the class and date for this register session.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="space-y-2">
              <Label for="register-class">Select Class</Label>
              <Select v-model="selectedClassId">
                <SelectTrigger id="register-class" class="h-10">
                  <SelectValue placeholder="Select class" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="cls in classes" :key="cls.id" :value="String(cls.id)">
                    {{ cls.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label for="register-date">Select Date</Label>
              <DatePicker
                id="register-date"
                v-model="registerDate"
                :max="todayIso"
                placeholder="Select date"
              />
            </div>

            <div class="space-y-2">
              <Label for="register-session">Session</Label>
              <Select v-model="sessionValue">
                <SelectTrigger id="register-session" class="h-10">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="full-day">Full day</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label for="register-sort">Sort By</Label>
              <Select v-model="sortBy">
                <SelectTrigger id="register-sort" class="h-10">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="name">Name</SelectItem>
                  <SelectItem value="id">Student ID</SelectItem>
                  <SelectItem value="status">Status</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_12rem]">
            <div class="relative">
              <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
              />
              <Input
                v-model="searchQuery"
                type="search"
                class="h-10 pl-9"
                placeholder="Search students by name or ID…"
                aria-label="Search students"
              />
            </div>
            <Select v-model="statusFilter">
              <SelectTrigger class="h-10" aria-label="Filter by status">
                <SelectValue placeholder="All Students" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Students</SelectItem>
                <SelectItem value="present">Present</SelectItem>
                <SelectItem value="late">Late</SelectItem>
                <SelectItem value="excused">Excused</SelectItem>
                <SelectItem value="absent">Absent</SelectItem>
                <SelectItem value="unmarked">Unmarked</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div
            v-if="registerDirty || registerComplete"
            class="flex flex-wrap items-center gap-2 text-xs"
          >
            <Badge
              v-if="registerDirty"
              variant="outline"
              class="border-amber-500/30 bg-amber-500/5 text-amber-700"
            >
              Unsaved changes
            </Badge>
            <Badge v-else-if="registerComplete" variant="secondary">Register complete</Badge>
          </div>
        </CardContent>
      </Card>

      <section
        aria-labelledby="attendance-stats"
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5"
      >
        <h2 id="attendance-stats" class="sr-only">Attendance summary</h2>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Present</p>
              <p class="text-3xl font-semibold tabular-nums">{{ stats.present }}</p>
              <p class="text-xs text-muted-foreground">{{ stats.presentPct }}%</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
              <CheckCircle2 class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Absent</p>
              <p class="text-3xl font-semibold tabular-nums">{{ stats.absent }}</p>
              <p class="text-xs text-muted-foreground">{{ stats.absentPct }}%</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-destructive/10 text-destructive">
              <UserX class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Late</p>
              <p class="text-3xl font-semibold tabular-nums">{{ stats.late }}</p>
              <p class="text-xs text-muted-foreground">{{ stats.latePct }}%</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
              <Clock class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Excused</p>
              <p class="text-3xl font-semibold tabular-nums">{{ stats.excused }}</p>
              <p class="text-xs text-muted-foreground">{{ stats.excusedPct }}%</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600">
              <BookOpen class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Total Students</p>
              <p class="text-3xl font-semibold tabular-nums">{{ stats.total }}</p>
              <p class="text-xs text-muted-foreground">{{ stats.unmarked }} unmarked</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
              <Users class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>
      </section>

      <Card class="border-border/70 shadow-sm">
        <CardContent class="space-y-3 px-5 py-5">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-medium text-foreground">Attendance Progress</p>
            <p class="text-sm text-muted-foreground">
              {{ stats.marked }} of {{ stats.total }} marked ({{ stats.progressPct }}%)
            </p>
          </div>
          <Progress :model-value="stats.progressPct" class="h-2.5" />
          <div class="flex flex-wrap gap-4 text-xs text-muted-foreground">
            <span class="inline-flex items-center gap-1.5">
              <span class="size-2 rounded-full bg-emerald-500" aria-hidden="true" /> Present
            </span>
            <span class="inline-flex items-center gap-1.5">
              <span class="size-2 rounded-full bg-amber-500" aria-hidden="true" /> Late
            </span>
            <span class="inline-flex items-center gap-1.5">
              <span class="size-2 rounded-full bg-blue-500" aria-hidden="true" /> Excused
            </span>
            <span class="inline-flex items-center gap-1.5">
              <span class="size-2 rounded-full bg-destructive" aria-hidden="true" /> Absent
            </span>
          </div>
        </CardContent>
      </Card>

      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList aria-label="Attendance views" class="flex h-auto flex-wrap gap-1">
          <TabsTrigger value="mark">Mark</TabsTrigger>
          <TabsTrigger value="history">History</TabsTrigger>
          <TabsTrigger value="reports">Reports</TabsTrigger>
          <TabsTrigger value="analytics" class="gap-1.5">
            Analytics
            <Badge
              v-if="classAtRisk.length"
              variant="destructive"
              class="h-5 min-w-5 rounded-full px-1.5 text-[10px]"
            >
              {{ classAtRisk.length }}
            </Badge>
          </TabsTrigger>
          <TabsTrigger value="audit">Audit Trail</TabsTrigger>
        </TabsList>

        <TabsContent value="mark" class="space-y-4">
          <Card class="border-border/70 shadow-sm">
            <CardHeader class="border-b border-border/60 pb-4">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                  <CardTitle class="flex items-center gap-2 text-base">
                    <CalendarDays class="size-4 text-muted-foreground" aria-hidden="true" />
                    Student Attendance
                    <span v-if="selectedClass">— {{ selectedClass.name }}</span>
                  </CardTitle>
                  <CardDescription>
                    {{ formatDate(registerDate) }} · Full day
                  </CardDescription>
                </div>
                <Badge variant="secondary">{{ filteredRows.length }} students</Badge>
              </div>
            </CardHeader>

            <CardContent class="p-0">
              <PageLoader v-if="registerLoading" class="py-12" label="Loading class register…" />
              <EmptyState
                v-else-if="!selectedClassId"
                variant="embedded"
                title="Select a class"
                description="Choose a class above to open the attendance register."
              />
              <EmptyState
                v-else-if="!registerRows.length"
                variant="embedded"
                title="No active students"
                description="This class has no active students to mark."
              />
              <EmptyState
                v-else-if="!filteredRows.length"
                variant="embedded"
                title="No matching students"
                description="Try adjusting your search or status filters."
              />
              <ul v-else class="divide-y divide-border/60" role="list">
                <li
                  v-for="row in filteredRows"
                  :key="row.student_id"
                  class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                >
                  <div class="min-w-0">
                    <p class="font-medium text-foreground">{{ row.full_name }}</p>
                    <p class="text-xs text-muted-foreground">ID: {{ row.student_number }}</p>
                  </div>
                  <div
                    class="flex flex-wrap gap-2"
                    role="group"
                    :aria-label="`Attendance for ${row.full_name}`"
                  >
                    <Button
                      v-for="opt in MARK_OPTIONS"
                      :key="opt.value"
                      type="button"
                      size="sm"
                      variant="outline"
                      :class="markButtonClass(opt, row.status === opt.value)"
                      :aria-pressed="row.status === opt.value"
                      :aria-label="`${opt.label} for ${row.full_name}`"
                      @click="setStatus(row, opt.value)"
                    >
                      <component :is="opt.icon" class="size-3.5" aria-hidden="true" />
                      {{ opt.label }}
                    </Button>
                  </div>
                </li>
              </ul>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="history">
          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Attendance history</CardTitle>
              <CardDescription>
                Recent marks{{ selectedClass ? ` for ${selectedClass.name}` : '' }}.
              </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <PageLoader v-if="historyLoading" class="py-12" label="Loading history…" />
              <EmptyState
                v-else-if="!historyRows.length"
                variant="embedded"
                title="No attendance records"
                description="Marked attendance for this class will appear here."
              />
              <div v-else class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date</TableHead>
                      <TableHead>Student</TableHead>
                      <TableHead class="hidden md:table-cell">Class</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead class="hidden lg:table-cell">Remarks</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="record in historyRows" :key="record.id">
                      <TableCell>{{ formatDate(record.date) }}</TableCell>
                      <TableCell class="font-medium">
                        {{ record.student?.full_name ?? '—' }}
                      </TableCell>
                      <TableCell class="hidden md:table-cell text-muted-foreground">
                        {{ record.class_model?.name ?? '—' }}
                      </TableCell>
                      <TableCell>
                        <Badge
                          :variant="statusBadgeVariant(record.status)"
                          class="capitalize"
                        >
                          {{ record.status }}
                        </Badge>
                      </TableCell>
                      <TableCell class="hidden lg:table-cell text-muted-foreground">
                        {{ record.remarks || '—' }}
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="reports">
          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Attendance report</CardTitle>
              <CardDescription>Last 30 days ending on the selected register date.</CardDescription>
            </CardHeader>
            <CardContent>
              <PageLoader v-if="reportLoading" class="py-12" label="Loading report…" />
              <template v-else-if="report">
                <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                  <div
                    v-for="(count, status) in report.by_status ?? {}"
                    :key="status"
                    class="rounded-xl border border-border/60 p-4"
                  >
                    <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                      {{ status }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">{{ count }}</p>
                  </div>
                </div>
                <div v-if="report.by_class?.length" class="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Class</TableHead>
                        <TableHead>Total</TableHead>
                        <TableHead>Present</TableHead>
                        <TableHead>Absent</TableHead>
                        <TableHead>Late</TableHead>
                        <TableHead>Excused</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow v-for="row in report.by_class" :key="String(row.class_id)">
                        <TableCell class="font-medium">{{ row.class_name ?? '—' }}</TableCell>
                        <TableCell>{{ row.total ?? 0 }}</TableCell>
                        <TableCell>{{ row.present ?? 0 }}</TableCell>
                        <TableCell>{{ row.absent ?? 0 }}</TableCell>
                        <TableCell>{{ row.late ?? 0 }}</TableCell>
                        <TableCell>{{ row.excused ?? 0 }}</TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
                <p v-else class="text-sm text-muted-foreground">No class breakdown available.</p>
              </template>
              <p v-else class="py-8 text-center text-sm text-muted-foreground">
                Could not load the attendance report.
              </p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="analytics" class="space-y-4">
          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Class analytics</CardTitle>
              <CardDescription>
                Cumulative attendance for {{ selectedClass?.name ?? 'the selected class' }}.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <PageLoader v-if="analyticsLoading" class="py-12" label="Loading analytics…" />
              <div
                v-else-if="classAnalytics"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
              >
                <div
                  v-for="key in ['total_students', 'total_records', 'present', 'absent', 'late', 'excused']"
                  :key="key"
                  class="rounded-xl border border-border/60 p-4"
                >
                  <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {{ key.replaceAll('_', ' ') }}
                  </p>
                  <p class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ classAnalytics[key] ?? 0 }}
                  </p>
                </div>
              </div>
              <p v-else class="py-8 text-center text-sm text-muted-foreground">
                Select a class to view analytics.
              </p>
            </CardContent>
          </Card>

          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Students at risk</CardTitle>
              <CardDescription>
                Learners flagged for low attendance or outstanding balances.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <ul v-if="classAtRisk.length" class="divide-y divide-border/60 rounded-xl border border-border/60">
                <li
                  v-for="student in classAtRisk"
                  :key="student.id"
                  class="flex items-center justify-between gap-3 px-4 py-3"
                >
                  <div>
                    <p class="font-medium">{{ student.full_name ?? 'Student' }}</p>
                    <p class="text-xs text-muted-foreground">
                      {{ student.student_number ?? '—' }}
                    </p>
                  </div>
                  <Button variant="outline" size="sm" as-child>
                    <RouterLink :to="`/students/${student.id}`">View</RouterLink>
                  </Button>
                </li>
              </ul>
              <p v-else class="text-sm text-muted-foreground">No at-risk students for this class.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="audit">
          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Attendance audit trail</CardTitle>
              <CardDescription>Recent attendance-related activity in the school.</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <PageLoader v-if="auditLoading" class="py-12" label="Loading audit trail…" />
              <EmptyState
                v-else-if="!auditRows.length"
                variant="embedded"
                title="No audit entries"
                description="Attendance changes will be logged here as the register is used."
              />
              <div v-else class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>When</TableHead>
                      <TableHead>Action</TableHead>
                      <TableHead>User</TableHead>
                      <TableHead>Details</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="row in auditRows" :key="row.id">
                      <TableCell class="whitespace-nowrap text-sm">
                        {{ formatDateTime(row.created_at) }}
                      </TableCell>
                      <TableCell class="capitalize">{{ row.action ?? '—' }}</TableCell>
                      <TableCell>{{ row.user?.name ?? '—' }}</TableCell>
                      <TableCell class="max-w-md truncate text-muted-foreground">
                        {{ row.description ?? '—' }}
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </PageShell>
</template>
