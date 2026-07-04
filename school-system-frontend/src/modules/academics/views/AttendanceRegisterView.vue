<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import {
  CheckCircle2,
  ClipboardCheck,
  Clock,
  Save,
  UserX,
  Users,
} from 'lucide-vue-next'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import DatePicker from '@/components/forms/DatePicker.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
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
import { Textarea } from '@/components/ui/textarea'
// Replaced legacy toaster primitive with native vue-sonner invocation hooks
import { toast } from 'vue-sonner'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { cn } from '@/lib/utils'
import { academicsApi } from '@/services/api.service'
import { fetchList } from '@/services/dashboard.service'
import { moduleEndpoints } from '@/services'

type AttendanceStatus = 'present' | 'absent' | 'late' | 'excused'

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
  status: AttendanceStatus
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

const STATUS_OPTIONS: { value: AttendanceStatus; label: string; short: string }[] = [
  { value: 'present', label: 'Present', short: 'P' },
  { value: 'absent', label: 'Absent', short: 'A' },
  { value: 'late', label: 'Late', short: 'L' },
  { value: 'excused', label: 'Excused', short: 'E' },
]

const loading = ref(true)
const registerLoading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const classes = ref<ClassOption[]>([])
const selectedClassId = ref('')
const registerDate = ref(new Date().toISOString().slice(0, 10))
const todayIso = computed(() => new Date().toISOString().slice(0, 10))
const registerRows = ref<RegisterRow[]>([])
const historyRows = ref<AttendanceRecord[]>([])
const historyLoading = ref(false)
const activeTab = ref('register')
const registerDirty = ref(false)

const selectedClass = computed(() =>
  classes.value.find((c) => String(c.id) === selectedClassId.value),
)

const stats = computed(() => {
  const total = registerRows.value.length
  const present = registerRows.value.filter((r) => r.status === 'present').length
  const absent = registerRows.value.filter((r) => r.status === 'absent').length
  const late = registerRows.value.filter((r) => r.status === 'late').length
  const excused = registerRows.value.filter((r) => r.status === 'excused').length
  const marked = present + absent + late + excused
  const rate = total > 0 ? Math.round((present / total) * 100) : 0

  return { total, present, absent, late, excused, marked, rate }
})

const registerComplete = computed(() =>
  registerRows.value.length > 0 && stats.value.marked === stats.value.total,
)

function statusButtonClass(status: AttendanceStatus, active: boolean) {
  const base = 'min-w-9 px-2 h-8 font-semibold transition-colors'
  if (!active) return cn(base, 'text-muted-foreground hover:bg-muted/50')
  switch (status) {
    case 'present':
      return cn(base, 'bg-emerald-600 text-white hover:bg-emerald-600/90 dark:bg-emerald-700')
    case 'absent':
      return cn(base, 'bg-destructive text-destructive-foreground hover:bg-destructive/90')
    case 'late':
      return cn(base, 'bg-amber-500 text-black hover:bg-amber-500/90 dark:bg-amber-600 dark:text-white')
    case 'excused':
      return cn(base, 'bg-blue-600 text-white hover:bg-blue-600/90 dark:bg-blue-700')
  }
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

function parseTimeIn(value: unknown) {
  if (!value) return ''
  const str = String(value)
  const match = str.match(/(\d{2}:\d{2})/)
  return match?.[1] ?? ''
}

async function loadClasses() {
  loading.value = true
  error.value = null
  try {
    classes.value = await academicsApi.classes.list() as ClassOption[]
    if (classes.value.length && !selectedClassId.value) {
      selectedClassId.value = String(classes.value[0].id)
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load classes')
  } finally {
    loading.value = false
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
        class_id: selectedClassId.value,
        limit: 500,
      }),
      academicsApi.attendance.list({
        class_id: selectedClassId.value,
        date: registerDate.value,
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
        full_name: student.full_name ?? `Student #${student.id}`,
        student_number: student.student_number ?? '—',
        status: saved?.status ?? 'present',
        time_in: parseTimeIn(saved?.time_in),
        remarks: saved?.remarks ?? '',
      }
    })

    registerDirty.value = false
  } catch (err) {
    toast.error('Could not load register', {
      description: getErrorMessage(err),
    })
    registerRows.value = []
  } finally {
    registerLoading.value = false
  }
}

async function loadHistory() {
  historyLoading.value = true
  try {
    const params: Record<string, string> = { limit: '200' }
    if (selectedClassId.value) params.class_id = selectedClassId.value
    historyRows.value = await academicsApi.attendance.list(params) as AttendanceRecord[]
  } catch {
    historyRows.value = []
  } finally {
    historyLoading.value = false
  }
}

function setStatus(row: RegisterRow, status: AttendanceStatus) {
  row.status = status
  registerDirty.value = true
}

function markAllPresent() {
  for (const row of registerRows.value) {
    row.status = 'present'
    row.time_in = ''
  }
  registerDirty.value = true
}

function markAllAbsent() {
  for (const row of registerRows.value) {
    row.status = 'absent'
  }
  registerDirty.value = true
}

async function saveRegister() {
  if (!selectedClassId.value || !registerRows.value.length) return

  const unmarked = registerRows.value.filter((r) => !r.status)
  if (unmarked.length) {
    toast.warning('Incomplete register', {
      description: 'Mark every learner before saving.',
    })
    return
  }

  saving.value = true
  try {
    await academicsApi.attendance.record({
      class_id: Number(selectedClassId.value),
      date: registerDate.value,
      records: registerRows.value.map((row) => ({
        student_id: row.student_id,
        status: row.status,
        remarks: row.remarks.trim() || null,
        time: row.status === 'late' && row.time_in ? row.time_in : null,
      })),
    })
    registerDirty.value = false
    toast.success('Attendance register saved successfully')
    await Promise.all([loadRegister(), loadHistory()])
  } catch (err) {
    toast.error('Save failed', {
      description: getErrorMessage(err),
    })
  } finally {
    saving.value = false
  }
}

watch([selectedClassId, registerDate], () => {
  void loadRegister()
})

watch(activeTab, (tab) => {
  if (tab === 'history') void loadHistory()
})

onMounted(async () => {
  await loadClasses()
  await loadRegister()
})
</script>

<template>
  <PageShell
    title="Attendance register"
    description="Daily class register — mark present, absent, late, or excused for each learner."
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
        <Save class="size-4 mr-2" aria-hidden="true" />
        {{ saving ? 'Saving…' : 'Save register' }}
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading register workspace…" />
    <ErrorState v-else-if="error" :description="error" @retry="loadClasses" />

    <div v-else class="space-y-6">
      <Card>
        <CardHeader class="pb-4">
          <CardTitle class="text-base font-semibold tracking-tight">Register session</CardTitle>
          <CardDescription class="text-xs">Select the class and date for this attendance session.</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
          <div class="space-y-2">
            <Label for="register-date">Date</Label>
            <DatePicker
              id="register-date"
              v-model="registerDate"
              :max="todayIso"
              placeholder="Select session date"
            />
          </div>

          <div class="space-y-2 sm:col-span-2">
            <Label for="register-class">Class</Label>
            <Select v-model="selectedClassId">
              <SelectTrigger id="register-class" class="h-10">
                <SelectValue placeholder="Select class" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="cls in classes" :key="cls.id" :value="String(cls.id)">
                  {{ cls.name }}<span v-if="cls.form" class="text-muted-foreground text-xs"> · {{ cls.form }}</span>
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="flex flex-col justify-end gap-1 text-sm bg-muted/20 p-3 rounded-xl border border-muted/60">
            <p class="font-semibold text-foreground leading-none">{{ selectedClass?.name ?? 'No class' }}</p>
            <p class="text-muted-foreground text-xs mt-1">{{ formatDate(registerDate) }}</p>
            <div class="mt-2">
              <Badge v-if="registerComplete && !registerDirty" variant="secondary" class="text-xs font-normal">Saved</Badge>
              <Badge v-else-if="registerDirty" variant="outline" class="text-xs text-amber-500 border-amber-500/30 bg-amber-500/5 font-normal">Unsaved changes</Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <section aria-labelledby="attendance-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <h2 id="attendance-kpis" class="sr-only">Register summary</h2>
        <KpiCard
          title="Enrolled"
          :value="String(stats.total)"
          subtitle="Learners in class"
          :icon="Users"
        />
        <KpiCard
          title="Present"
          :value="String(stats.present)"
          :subtitle="`${stats.rate}% attendance`"
          :icon="CheckCircle2"
          accent="success"
        />
        <KpiCard
          title="Absent"
          :value="String(stats.absent)"
          subtitle="Requires follow-up"
          :icon="UserX"
          accent="danger"
        />
        <KpiCard
          title="Late"
          :value="String(stats.late)"
          subtitle="Arrived after start"
          :icon="Clock"
          accent="warning"
        />
        <KpiCard
          title="Excused"
          :value="String(stats.excused)"
          subtitle="Approved absence"
          :icon="ClipboardCheck"
        />
      </section>

      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList aria-label="Attendance views">
          <TabsTrigger value="register">Daily register</TabsTrigger>
          <TabsTrigger value="history">History</TabsTrigger>
        </TabsList>

        <TabsContent value="register" class="space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs text-muted-foreground leading-none">
              Tap <strong>P</strong> present, <strong>A</strong> absent, <strong>L</strong> late, <strong>E</strong> excused.
            </p>
            <Button variant="ghost" size="sm" class="h-8 text-xs px-2.5 text-muted-foreground hover:text-foreground" @click="markAllAbsent">
              Mark all absent
            </Button>
          </div>

          <Card>
            <CardContent class="p-0">
              <PageLoader v-if="registerLoading" class="py-12" label="Loading class register matrix…" />
              <p
                v-else-if="!selectedClassId"
                class="py-12 text-center text-sm text-muted-foreground italic"
              >
                Select a class to open the register.
              </p>
              <p
                v-else-if="!registerRows.length"
                class="py-12 text-center text-sm text-muted-foreground italic"
              >
                No active students in this class.
              </p>
              <div v-else class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead class="w-12 text-xs font-medium">#</TableHead>
                      <TableHead class="text-xs font-medium">Student</TableHead>
                      <TableHead class="hidden sm:table-cell text-xs font-medium">Student no.</TableHead>
                      <TableHead class="min-w-[220px] text-xs font-medium">Status</TableHead>
                      <TableHead class="w-28 text-xs font-medium">Time in</TableHead>
                      <TableHead class="min-w-[180px] text-xs font-medium">Remarks</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="(row, index) in registerRows" :key="row.student_id" class="transition-colors">
                      <TableCell class="text-muted-foreground font-mono text-xs">{{ index + 1 }}</TableCell>
                      <TableCell class="font-medium text-sm text-foreground">{{ row.full_name }}</TableCell>
                      <TableCell class="hidden text-muted-foreground sm:table-cell text-xs font-mono">
                        {{ row.student_number }}
                      </TableCell>
                      <TableCell>
                        <div
                          class="flex flex-wrap gap-1"
                          role="group"
                          :aria-label="`Attendance for ${row.full_name}`"
                        >
                          <Button
                            v-for="opt in STATUS_OPTIONS"
                            :key="opt.value"
                            type="button"
                            size="sm"
                            variant="outline"
                            :class="statusButtonClass(opt.value, row.status === opt.value)"
                            :aria-pressed="row.status === opt.value"
                            :aria-label="`${opt.label} for ${row.full_name}`"
                            @click="setStatus(row, opt.value)"
                          >
                            {{ opt.short }}
                          </Button>
                        </div>
                      </TableCell>
                      <TableCell>
                        <Input
                          v-model="row.time_in"
                          type="time"
                          class="h-8 text-xs bg-background"
                          :disabled="row.status !== 'late'"
                          :aria-label="`Time in for ${row.full_name}`"
                          @input="registerDirty = true"
                        />
                      </TableCell>
                      <TableCell>
                        <Textarea
                          v-model="row.remarks"
                          rows="1"
                          class="min-h-8 bg-background resize-none py-1.5 text-xs leading-normal"
                          placeholder="Optional note"
                          :aria-label="`Remarks for ${row.full_name}`"
                          @input="registerDirty = true"
                        />
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="history">
          <Card>
            <CardHeader>
              <CardTitle class="text-base font-semibold tracking-tight">Attendance history</CardTitle>
              <CardDescription class="text-xs">
                Recent marks{{ selectedClass ? ` for ${selectedClass.name}` : '' }}.
              </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <PageLoader v-if="historyLoading" class="py-12" label="Loading historical entries…" />
              <p v-else-if="!historyRows.length" class="py-12 text-center text-sm text-muted-foreground italic">
                No attendance records yet.
              </p>
              <div v-else class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead class="text-xs font-medium">Date</TableHead>
                      <TableHead class="text-xs font-medium">Student</TableHead>
                      <TableHead class="hidden md:table-cell text-xs font-medium">Class</TableHead>
                      <TableHead class="text-xs font-medium">Status</TableHead>
                      <TableHead class="hidden lg:table-cell text-xs font-medium">Remarks</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="record in historyRows" :key="record.id" class="transition-colors">
                      <TableCell class="text-sm text-foreground">{{ formatDate(record.date) }}</TableCell>
                      <TableCell class="font-medium text-sm text-foreground">{{ record.student?.full_name ?? '—' }}</TableCell>
                      <TableCell class="hidden md:table-cell text-sm text-muted-foreground">
                        {{ record.class_model?.name ?? '—' }}
                      </TableCell>
                      <TableCell>
                        <Badge :variant="statusBadgeVariant(record.status)" class="font-normal text-xs capitalize">
                          {{ record.status }}
                        </Badge>
                      </TableCell>
                      <TableCell class="hidden max-w-xs truncate text-xs text-muted-foreground lg:table-cell">
                        {{ record.remarks ?? '—' }}
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
