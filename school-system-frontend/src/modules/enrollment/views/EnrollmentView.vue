<script setup lang="ts">
import { computed, h, nextTick, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import { Plus } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import PageShell from '@/components/layout/PageShell.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { badgeVariantForStatus } from '@/lib/ui-status'
import { formatDate } from '@/lib/format'
import { cn } from '@/lib/utils'
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useToast } from '@/composables/useToast'
import { useFormSheetLoader } from '@/composables/useFormSheetLoader'
import { getErrorMessage } from '@/lib/api-response'
import { academicStructureApi, enrollmentApi } from '@/services/api.service'
import { fetchList, moduleEndpoints } from '@/services'
import { enrollmentFormFields, enrollmentFormSchema } from '@/modules/enrollment/enrollment-form'
import { mapFormToPayload } from '@/modules/shared/crud-mappers'

interface Application {
  id: number
  first_name: string
  surname: string
  gender?: string
  grade_applying_for?: string
  academic_year?: string
  status: string
  created_at?: string
  student_id?: number | null
  guardian_first_name?: string
  guardian_surname?: string
  guardian_phone?: string
}

interface ClassOption {
  id: number
  name: string
  grade_level_id?: number
  stream_id?: number | null
  capacity?: number
  current_enrollment?: number
  teacher?: { full_name?: string; first_name?: string; last_name?: string }
}

interface NamedOption {
  id: number
  name: string
}

const router = useRouter()
const toast = useToast()
const { academicYearStart } = useAcademicYearFilter()
const rows = ref<Application[]>([])
const statusFilter = ref('all')
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const saving = ref(false)
const approving = ref(false)
const formResetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const approveOpen = ref(false)
const approveTarget = ref<Application | null>(null)
const classes = ref<ClassOption[]>([])
const streams = ref<NamedOption[]>([])
const houses = ref<NamedOption[]>([])
const selectedClassId = ref<string>('')
const selectedStreamId = ref<string>('')
const selectedHouseId = ref<string>('')

const { formLoading, prepareCreate } = useFormSheetLoader(() => ({
  endpoint: moduleEndpoints.enrollment,
  formFields: enrollmentFormFields,
  setFormValues: (values) => { formResetValues.value = values },
}))

const filteredClasses = computed(() => {
  const stream = selectedStreamId.value
  if (!stream || stream === 'none') return classes.value
  return classes.value.filter((c) => String(c.stream_id ?? '') === stream || c.stream_id == null)
})

const selectedClass = computed(() =>
  classes.value.find((c) => String(c.id) === selectedClassId.value) ?? null,
)

const homeroomLabel = computed(() => {
  const t = selectedClass.value?.teacher
  if (!t) return 'Not assigned on class'
  return t.full_name || [t.first_name, t.last_name].filter(Boolean).join(' ') || 'Not assigned'
})

const capacityLabel = computed(() => {
  const c = selectedClass.value
  if (!c?.capacity) return 'No capacity limit'
  const used = Number(c.current_enrollment ?? 0)
  return `${used} / ${c.capacity} places filled`
})

const filteredRows = computed(() => {
  let list = rows.value
  const year = academicYearStart.value
  if (year) {
    list = list.filter((r) => !r.academic_year || String(r.academic_year).includes(year))
  }
  if (statusFilter.value === 'enrolled') {
    return list.filter((r) => r.status === 'approved' && r.student_id)
  }
  if (statusFilter.value !== 'all') {
    return list.filter((r) => r.status === statusFilter.value)
  }
  return list
})

const kpi = computed(() => {
  const all = rows.value
  const count = (s: string) => all.filter((r) => r.status === s).length
  return {
    total: all.length,
    pending: count('pending'),
    under_review: count('under_review'),
    interview: count('interview'),
    fee_pending: count('fee_pending'),
    approved: count('approved'),
    enrolled: all.filter((r) => r.status === 'approved' && r.student_id).length,
  }
})

const statusChips = computed(() => [
  { key: 'all', label: 'All', count: kpi.value.total },
  { key: 'pending', label: 'Pending', count: kpi.value.pending },
  { key: 'under_review', label: 'Under Review', count: kpi.value.under_review },
  { key: 'interview', label: 'Interviews', count: kpi.value.interview },
  { key: 'fee_pending', label: 'Fee Pending', count: kpi.value.fee_pending },
  { key: 'approved', label: 'Approved', count: kpi.value.approved },
  { key: 'enrolled', label: 'Enrolled', count: kpi.value.enrolled },
])

function admId(app: Application) {
  const y = String(app.academic_year ?? new Date().getFullYear()).match(/\d{4}/)?.[0] ?? '2026'
  return `ADM${y}${String(app.id).padStart(4, '0')}`
}

const columns: ColumnDef<Record<string, unknown>>[] = [
  {
    id: 'applicant',
    header: 'Applicant',
    cell: ({ row }) => {
      const app = row.original as unknown as Application
      const name = `${app.first_name} ${app.surname}`.trim()
      const initials = name.split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || 'AP'
      return h('div', { class: 'flex items-center gap-3' }, [
        h(Avatar, { class: 'size-10' }, () => h(AvatarFallback, { class: 'text-xs font-semibold' }, () => initials)),
        h('div', { class: 'min-w-0' }, [
          h('p', { class: 'font-semibold truncate' }, name),
          h('p', { class: 'text-xs text-muted-foreground' }, `${admId(app)} · ${app.gender ?? '—'}`),
        ]),
      ])
    },
  },
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const app = row.original as unknown as Application
      return h(Badge, { variant: badgeVariantForStatus(app.status), class: 'capitalize' }, () =>
        String(app.status ?? '—').replaceAll('_', ' '),
      )
    },
  },
  {
    id: 'class',
    header: 'Class Applied',
    cell: ({ row }) => String((row.original as unknown as Application).grade_applying_for ?? '—'),
  },
  {
    id: 'parent',
    header: 'Parent',
    cell: ({ row }) => {
      const app = row.original as unknown as Application
      const parent = [app.guardian_first_name, app.guardian_surname].filter(Boolean).join(' ') || '—'
      return h('div', { class: 'text-sm' }, [
        h('p', parent),
        h('p', { class: 'text-xs text-muted-foreground' }, app.guardian_phone || '—'),
      ])
    },
  },
  {
    id: 'applied',
    header: 'Applied Date',
    cell: ({ row }) => formatDate((row.original as unknown as Application).created_at),
  },
  {
    id: 'actions',
    header: () => h('span', { class: 'sr-only' }, 'Actions'),
    cell: ({ row }) => {
      const app = row.original as unknown as Application
      const actions = []
      if (['pending', 'under_review', 'interview', 'fee_pending', 'waitlisted'].includes(app.status)) {
        actions.push(
          h(Button, { size: 'sm', variant: 'default', class: 'rounded-xl', onClick: () => openApprove(app) }, () => 'Approve'),
          h(Button, { size: 'sm', variant: 'outline', class: 'rounded-xl', onClick: () => reject(app.id) }, () => 'Reject'),
        )
        if (app.status === 'pending') {
          actions.push(
            h(Button, {
              size: 'sm',
              variant: 'secondary',
              class: 'rounded-xl',
              onClick: () => setStatus(app.id, 'under_review'),
            }, () => 'Review'),
          )
        }
      }
      return h('div', { class: 'flex flex-wrap justify-end gap-1' }, actions)
    },
  },
]

const tableData = computed(() => filteredRows.value as unknown as Record<string, unknown>[])
const { table, globalFilter } = useDataTable({ data: tableData as never, columns })

async function loadOptions() {
  const [classRows, streamRows, houseRows] = await Promise.all([
    fetchList(moduleEndpoints.classes, { all: true }).catch(() => []),
    academicStructureApi.streams.list({ all: true }).catch(() => []),
    academicStructureApi.houses.list({ all: true }).catch(() => []),
  ])
  classes.value = classRows as ClassOption[]
  streams.value = streamRows as NamedOption[]
  houses.value = houseRows as NamedOption[]
}

async function load() {
  loading.value = true
  error.value = null
  try {
    rows.value = await enrollmentApi.list() as Application[]
    await loadOptions()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load applications'
  } finally {
    loading.value = false
  }
}

function openApprove(app: Application) {
  approveTarget.value = app
  selectedClassId.value = ''
  selectedStreamId.value = 'none'
  selectedHouseId.value = 'none'

  const gradeName = String(app.grade_applying_for ?? '').toLowerCase()
  const match = classes.value.find((c) =>
    String(c.name ?? '').toLowerCase().includes(gradeName) || gradeName.includes(String(c.name ?? '').toLowerCase()),
  )
  if (match) selectedClassId.value = String(match.id)

  approveOpen.value = true
}

async function confirmApprove() {
  if (!approveTarget.value) return
  if (!selectedClassId.value) {
    toast.error('Select a class', 'Choose the class, stream, and house placement before approving.')
    return
  }

  approving.value = true
  try {
    const result = await enrollmentApi.approve(approveTarget.value.id, {
      class_id: Number(selectedClassId.value),
      stream_id: selectedStreamId.value && selectedStreamId.value !== 'none'
        ? Number(selectedStreamId.value)
        : null,
      house_id: selectedHouseId.value && selectedHouseId.value !== 'none'
        ? Number(selectedHouseId.value)
        : null,
    }) as { student?: { id?: number } }

    toast.success('Application approved', 'Student profile created and placed.')
    approveOpen.value = false
    await load()

    const studentId = result?.student?.id
    if (studentId) {
      await router.push(`/students/${studentId}`)
    }
  } catch (err) {
    toast.error('Approve failed', getErrorMessage(err))
  } finally {
    approving.value = false
  }
}

async function reject(id: number) {
  try {
    await enrollmentApi.reject(id, { notes: 'Rejected from admin panel' })
    toast.success('Application rejected')
    await load()
  } catch (err) {
    toast.error('Reject failed', getErrorMessage(err))
  }
}

async function setStatus(id: number, status: string) {
  try {
    await enrollmentApi.updateStatus(id, { status })
    toast.success('Status updated')
    await load()
  } catch (err) {
    toast.error('Status update failed', getErrorMessage(err))
  }
}

async function onSubmit(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = mapFormToPayload('enrollment', values, enrollmentFormFields)
    await enrollmentApi.create(payload)
    toast.success('Application submitted')
    sheetOpen.value = false
    await load()
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast.error('Submit failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function openCreate() {
  formResetValues.value = {}
  sheetOpen.value = true
  await nextTick()
  await prepareCreate()
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Admissions"
    description="Review applications, approve enrolment, and place students in classes."
    :breadcrumbs="['Application', 'Admissions']"
    max-width="wide"
  >
    <template #actions>
      <Button class="rounded-xl" @click="openCreate">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        New Admission
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading enrollment applications" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
        <KpiCard title="Total Applications" :value="String(kpi.total)" />
        <KpiCard title="Pending Review" :value="String(kpi.pending)" />
        <KpiCard title="Under Review" :value="String(kpi.under_review)" />
        <KpiCard title="Interviews" :value="String(kpi.interview)" />
        <KpiCard title="Fee Pending" :value="String(kpi.fee_pending)" />
        <KpiCard title="Approved" :value="String(kpi.approved)" />
        <KpiCard title="Enrolled" :value="String(kpi.enrolled)" />
      </div>

      <div class="mb-4 flex flex-wrap gap-2">
        <button
          v-for="chip in statusChips"
          :key="chip.key"
          type="button"
          :class="cn(
            'rounded-full border px-3 py-1 text-xs font-medium',
            statusFilter === chip.key ? 'border-primary/40 bg-primary/10 text-primary' : 'border-border text-muted-foreground',
          )"
          @click="statusFilter = chip.key"
        >
          {{ chip.label }} {{ chip.count }}
        </button>
      </div>

      <DataTable
        :table="table"
        :columns="columns"
        :global-filter="globalFilter"
        search-placeholder="Search applicant…"
        @update:global-filter="globalFilter = $event"
      />
    </template>
  </PageShell>

  <Dialog :open="approveOpen" @update:open="approveOpen = $event">
    <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-2xl sm:rounded-xl">
      <DialogHeader class="space-y-1.5 border-b border-border/60 px-6 pt-6 pb-4">
        <DialogTitle class="text-lg font-medium tracking-tight">Approve and place student</DialogTitle>
        <DialogDescription class="text-sm leading-relaxed text-muted-foreground">
          {{ approveTarget?.first_name }} {{ approveTarget?.surname }}
          · {{ approveTarget?.grade_applying_for }}
          · {{ approveTarget?.academic_year }}
        </DialogDescription>
      </DialogHeader>

      <div class="grid grid-cols-1 gap-x-4 gap-y-5 px-6 py-5 sm:grid-cols-2">
        <div class="space-y-2">
          <Label for="approve-stream" class="text-sm font-medium">Stream (optional)</Label>
          <Select v-model="selectedStreamId">
            <SelectTrigger id="approve-stream" class="h-10 w-full">
              <SelectValue placeholder="Any stream" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">Any stream</SelectItem>
              <SelectItem v-for="s in streams" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="approve-class" class="text-sm font-medium">Class</Label>
          <Select v-model="selectedClassId">
            <SelectTrigger id="approve-class" class="h-10 w-full">
              <SelectValue placeholder="Select class" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="c in filteredClasses" :key="c.id" :value="String(c.id)">
                {{ c.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p class="min-h-[1.25rem] text-sm text-muted-foreground">
            Homeroom: {{ homeroomLabel }} · {{ capacityLabel }}
          </p>
        </div>

        <div class="space-y-2 sm:col-span-2">
          <Label for="approve-house" class="text-sm font-medium">House (optional)</Label>
          <Select v-model="selectedHouseId">
            <SelectTrigger id="approve-house" class="h-10 w-full">
              <SelectValue placeholder="No house" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">No house</SelectItem>
              <SelectItem v-for="h in houses" :key="h.id" :value="String(h.id)">{{ h.name }}</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <DialogFooter class="flex-row justify-end gap-2 border-t border-border/60 px-6 py-4 sm:space-x-0">
        <Button variant="outline" :disabled="approving" @click="approveOpen = false">Cancel</Button>
        <Button class="min-w-[8rem]" :disabled="approving || !selectedClassId" @click="confirmApprove">
          {{ approving ? 'Approving…' : 'Approve & place' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <FormSheet
    ref="formSheetRef"
    v-model:open="sheetOpen"
    title="New enrollment application"
    description="Submit a new student application. All guardian and emergency contact fields are required."
    :fields="enrollmentFormFields"
    :schema="enrollmentFormSchema"
    :reset-values="formResetValues"
    form-key="enrollment-create"
    :form-loading="formLoading"
    :saving="saving"
    save-label="Submit application"
    saving-label="Submitting…"
    @submit="onSubmit"
  />
</template>
