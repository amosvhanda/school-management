<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ClipboardCheck, Save, Users } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import DatePicker from '@/components/forms/DatePicker.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
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
import { getErrorMessage } from '@/lib/api-response'
import { todayIsoDate } from '@/lib/validation'
import { hrApi } from '@/services/api.service'

type StaffStatus = 'present' | 'absent' | 'late' | 'half_day' | 'on_leave' | ''
type ViewTab = 'register' | 'summary'

interface RosterRow {
  staff_type: 'teacher' | 'employee'
  staff_id: number
  name: string
  employee_number?: string | null
  email?: string | null
  department?: string | null
  designation?: string | null
  status: StaffStatus
  remarks?: string | null
  attendance_id?: number | null
}

interface StaffSummaryRow {
  staff_type: string
  staff_id: number
  name: string
  present: number
  absent: number
  late: number
  half_day: number
  on_leave: number
  marked_days: number
}

const STATUS_OPTIONS: { value: Exclude<StaffStatus, ''>; label: string }[] = [
  { value: 'present', label: 'Present' },
  { value: 'absent', label: 'Absent' },
  { value: 'late', label: 'Late' },
  { value: 'half_day', label: 'Half day' },
  { value: 'on_leave', label: 'On leave' },
]

const MONTH_OPTIONS = [
  { value: '1', label: 'January' },
  { value: '2', label: 'February' },
  { value: '3', label: 'March' },
  { value: '4', label: 'April' },
  { value: '5', label: 'May' },
  { value: '6', label: 'June' },
  { value: '7', label: 'July' },
  { value: '8', label: 'August' },
  { value: '9', label: 'September' },
  { value: '10', label: 'October' },
  { value: '11', label: 'November' },
  { value: '12', label: 'December' },
]

const activeTab = ref<ViewTab>('register')
const loading = ref(true)
const saving = ref(false)
const error = ref<string | null>(null)
const registerDate = ref(todayIsoDate())
const teachers = ref<RosterRow[]>([])
const employees = ref<RosterRow[]>([])
const dirty = ref(false)

const route = useRoute()
const staffFilter = computed<'teacher' | 'employee' | 'all'>(() => {
  const raw = String(Array.isArray(route.query.staff) ? route.query.staff[0] : route.query.staff ?? '')
  if (raw === 'teacher' || raw === 'employee') return raw
  return 'all'
})

const pageTitle = computed(() => {
  if (staffFilter.value === 'teacher') return 'Teacher attendance'
  if (staffFilter.value === 'employee') return 'Employee attendance'
  return 'Staff attendance'
})

const pageDescription = computed(() => {
  if (staffFilter.value === 'teacher') return 'Mark daily teacher attendance and review monthly totals.'
  if (staffFilter.value === 'employee') return 'Mark daily employee attendance and review monthly totals.'
  return 'Mark daily attendance and review monthly staff totals.'
})

const rosterSections = computed(() => {
  const sections = [
    { title: 'Teachers', rows: teachers.value, key: 'teachers' as const },
    { title: 'Employees', rows: employees.value, key: 'employees' as const },
  ]
  if (staffFilter.value === 'teacher') return sections.filter((s) => s.key === 'teachers')
  if (staffFilter.value === 'employee') return sections.filter((s) => s.key === 'employees')
  return sections
})

const summaryLoading = ref(false)
const summaryError = ref<string | null>(null)
const summaryYear = ref(String(new Date().getFullYear()))
const summaryMonth = ref(String(new Date().getMonth() + 1))
const summaryStaff = ref<StaffSummaryRow[]>([])
const summaryByStatus = ref<Record<string, number>>({})
const summaryTotalMarks = ref(0)

const allRows = computed(() => {
  if (staffFilter.value === 'teacher') return teachers.value
  if (staffFilter.value === 'employee') return employees.value
  return [...teachers.value, ...employees.value]
})

const filteredSummaryStaff = computed(() => {
  if (staffFilter.value === 'teacher') {
    return summaryStaff.value.filter((row) => row.staff_type === 'teacher')
  }
  if (staffFilter.value === 'employee') {
    return summaryStaff.value.filter((row) => row.staff_type === 'employee')
  }
  return summaryStaff.value
})

const stats = computed(() => {
  const total = allRows.value.length
  const marked = allRows.value.filter((r) => r.status).length
  const present = allRows.value.filter((r) => r.status === 'present').length
  const absent = allRows.value.filter((r) => r.status === 'absent').length
  return { total, marked, present, absent }
})

const yearOptions = computed(() => {
  const current = new Date().getFullYear()
  return Array.from({ length: 6 }, (_, i) => String(current - i))
})

function normalizeRow(raw: Record<string, unknown>): RosterRow {
  return {
    staff_type: raw.staff_type === 'employee' ? 'employee' : 'teacher',
    staff_id: Number(raw.staff_id),
    name: String(raw.name ?? '—'),
    employee_number: raw.employee_number != null ? String(raw.employee_number) : null,
    email: raw.email != null ? String(raw.email) : null,
    department: raw.department != null ? String(raw.department) : null,
    designation: raw.designation != null ? String(raw.designation) : null,
    status: (raw.status as StaffStatus) || '',
    remarks: raw.remarks != null ? String(raw.remarks) : null,
    attendance_id: raw.attendance_id != null ? Number(raw.attendance_id) : null,
  }
}

async function loadRoster() {
  loading.value = true
  error.value = null
  try {
    const data = await hrApi.staffAttendance.roster({ date: registerDate.value })
    const payload = (data ?? {}) as {
      teachers?: Record<string, unknown>[]
      employees?: Record<string, unknown>[]
    }
    teachers.value = (payload.teachers ?? []).map(normalizeRow)
    employees.value = (payload.employees ?? []).map(normalizeRow)
    dirty.value = false
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load staff roster')
    teachers.value = []
    employees.value = []
  } finally {
    loading.value = false
  }
}

async function loadSummary() {
  summaryLoading.value = true
  summaryError.value = null
  try {
    const data = await hrApi.staffAttendance.summary({
      year: Number(summaryYear.value),
      month: Number(summaryMonth.value),
    })
    const payload = (data ?? {}) as {
      staff?: StaffSummaryRow[]
      by_status?: Record<string, number>
      total_marks?: number
    }
    summaryStaff.value = payload.staff ?? []
    summaryByStatus.value = payload.by_status ?? {}
    summaryTotalMarks.value = Number(payload.total_marks ?? 0)
  } catch (err) {
    summaryError.value = getErrorMessage(err, 'Failed to load monthly summary')
    summaryStaff.value = []
    summaryByStatus.value = {}
    summaryTotalMarks.value = 0
  } finally {
    summaryLoading.value = false
  }
}

function setStatus(row: RosterRow, status: string) {
  row.status = (status as StaffStatus) || ''
  dirty.value = true
}

async function saveAttendance() {
  const entries = allRows.value
    .filter((r) => r.status)
    .map((r) => ({
      staff_type: r.staff_type,
      staff_id: r.staff_id,
      status: r.status,
      ...(r.remarks ? { remarks: r.remarks } : {}),
    }))

  if (!entries.length) {
    toast.error('Mark at least one staff member before saving')
    return
  }

  saving.value = true
  try {
    await hrApi.staffAttendance.store({
      date: registerDate.value,
      entries,
    })
    toast.success('Staff attendance saved')
    dirty.value = false
    await loadRoster()
  } catch (err) {
    toast.error(getErrorMessage(err, 'Failed to save attendance'))
  } finally {
    saving.value = false
  }
}

watch(registerDate, () => {
  void loadRoster()
})

watch([summaryYear, summaryMonth], () => {
  if (activeTab.value === 'summary') void loadSummary()
})

watch(activeTab, (tab) => {
  if (tab === 'summary') void loadSummary()
})

onMounted(() => {
  void loadRoster()
})
</script>

<template>
  <PageShell
    :title="pageTitle"
    :description="pageDescription"
    max-width="wide"
  >
    <template #actions>
      <Button
        v-if="activeTab === 'register'"
        type="button"
        :disabled="saving || loading || !dirty"
        @click="saveAttendance"
      >
        <Save class="size-4" aria-hidden="true" />
        {{ saving ? 'Saving…' : 'Save attendance' }}
      </Button>
    </template>

    <Tabs v-model="activeTab" class="space-y-6">
      <TabsList>
        <TabsTrigger value="register">Daily register</TabsTrigger>
        <TabsTrigger value="summary">Monthly summary</TabsTrigger>
      </TabsList>

      <TabsContent value="register" class="space-y-6">
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Register date</CardTitle>
            <CardDescription>Load the roster for a specific day, then mark and save.</CardDescription>
          </CardHeader>
          <CardContent class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="space-y-2 sm:w-56">
              <Label for="staff-attendance-date">Date</Label>
              <DatePicker
                id="staff-attendance-date"
                v-model="registerDate"
              />
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-muted-foreground" aria-live="polite">
              <span>{{ stats.total }} staff</span>
              <span>{{ stats.marked }} marked</span>
              <span>{{ stats.present }} present</span>
              <span>{{ stats.absent }} absent</span>
            </div>
          </CardContent>
        </Card>

        <PageLoader v-if="loading" label="Loading staff roster…" />
        <ErrorState
          v-else-if="error"
          :message="error"
          @retry="loadRoster"
        />
        <EmptyState
          v-else-if="!allRows.length"
          title="No staff on roster"
          description="Add active teachers or employees to take attendance."
          :icon="Users"
        />

        <div v-else class="space-y-6">
          <Card
            v-for="section in rosterSections"
            :key="section.key"
          >
            <CardHeader class="pb-3">
              <CardTitle class="flex items-center gap-2 text-base">
                <ClipboardCheck class="size-4" aria-hidden="true" />
                {{ section.title }}
              </CardTitle>
              <CardDescription>{{ section.rows.length }} people</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <EmptyState
                v-if="!section.rows.length"
                variant="embedded"
                class="py-8"
                title="None listed"
                description="No active records in this group for today."
              />
              <Table v-else>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Number</TableHead>
                    <TableHead>Role / dept</TableHead>
                    <TableHead class="w-44">Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="row in section.rows" :key="`${row.staff_type}-${row.staff_id}`">
                    <TableCell class="font-medium">{{ row.name }}</TableCell>
                    <TableCell class="text-muted-foreground">{{ row.employee_number || '—' }}</TableCell>
                    <TableCell class="text-muted-foreground">
                      {{ row.designation || row.department || '—' }}
                    </TableCell>
                    <TableCell>
                      <Select
                        :model-value="row.status || undefined"
                        @update:model-value="(v) => setStatus(row, String(v ?? ''))"
                      >
                        <SelectTrigger :aria-label="`Status for ${row.name}`">
                          <SelectValue placeholder="Unmarked" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem
                            v-for="opt in STATUS_OPTIONS"
                            :key="opt.value"
                            :value="opt.value"
                          >
                            {{ opt.label }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </div>
      </TabsContent>

      <TabsContent value="summary" class="space-y-6">
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Month</CardTitle>
            <CardDescription>Totals for teachers and employees marked this month.</CardDescription>
          </CardHeader>
          <CardContent class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="space-y-2 sm:w-40">
              <Label for="staff-summary-month">Month</Label>
              <Select v-model="summaryMonth">
                <SelectTrigger id="staff-summary-month">
                  <SelectValue placeholder="Month" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="m in MONTH_OPTIONS" :key="m.value" :value="m.value">
                    {{ m.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2 sm:w-32">
              <Label for="staff-summary-year">Year</Label>
              <Select v-model="summaryYear">
                <SelectTrigger id="staff-summary-year">
                  <SelectValue placeholder="Year" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="y in yearOptions" :key="y" :value="y">
                    {{ y }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-muted-foreground" aria-live="polite">
              <span>{{ summaryTotalMarks }} marks</span>
              <span>{{ summaryByStatus.present ?? 0 }} present</span>
              <span>{{ summaryByStatus.absent ?? 0 }} absent</span>
              <span>{{ summaryByStatus.late ?? 0 }} late</span>
              <span>{{ summaryByStatus.on_leave ?? 0 }} on leave</span>
            </div>
          </CardContent>
        </Card>

        <PageLoader v-if="summaryLoading" label="Loading monthly summary…" />
        <ErrorState
          v-else-if="summaryError"
          :message="summaryError"
          @retry="loadSummary"
        />
        <EmptyState
          v-else-if="!filteredSummaryStaff.length"
          title="No attendance marked"
          description="Mark daily registers first, then return here for monthly totals."
          :icon="ClipboardCheck"
        />
        <Card v-else>
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Per staff</CardTitle>
            <CardDescription>{{ filteredSummaryStaff.length }} people with marks this month</CardDescription>
          </CardHeader>
          <CardContent class="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Present</TableHead>
                  <TableHead>Absent</TableHead>
                  <TableHead>Late</TableHead>
                  <TableHead>Half day</TableHead>
                  <TableHead>On leave</TableHead>
                  <TableHead>Marked days</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="row in filteredSummaryStaff"
                  :key="`${row.staff_type}-${row.staff_id}`"
                >
                  <TableCell class="font-medium">{{ row.name }}</TableCell>
                  <TableCell class="capitalize text-muted-foreground">{{ row.staff_type }}</TableCell>
                  <TableCell>{{ row.present }}</TableCell>
                  <TableCell>{{ row.absent }}</TableCell>
                  <TableCell>{{ row.late }}</TableCell>
                  <TableCell>{{ row.half_day }}</TableCell>
                  <TableCell>{{ row.on_leave }}</TableCell>
                  <TableCell>{{ row.marked_days }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>
  </PageShell>
</template>
