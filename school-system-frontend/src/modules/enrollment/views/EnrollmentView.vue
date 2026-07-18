<script setup lang="ts">
import { computed, h, nextTick, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import { Plus } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import PageShell from '@/components/layout/PageShell.vue'
import { Button } from '@/components/ui/button'
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
import { dateColumn, statusColumn, textColumn } from '@/modules/shared/columns'
import { enrollmentFormFields, enrollmentFormSchema } from '@/modules/enrollment/enrollment-form'

interface Application {
  id: number
  first_name: string
  surname: string
  grade_applying_for?: string
  academic_year?: string
  status: string
  created_at?: string
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
const rows = ref<Application[]>([])
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

const columns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Applicant', 'first_name'),
  textColumn('Surname', 'surname'),
  textColumn('Grade', 'grade_applying_for'),
  textColumn('Year', 'academic_year'),
  statusColumn(),
  dateColumn('Applied', 'created_at'),
  {
    id: 'actions',
    header: () => h('span', { class: 'sr-only' }, 'Actions'),
    cell: ({ row }) => {
      const app = row.original as unknown as Application
      if (app.status !== 'pending') return null
      return h('div', { class: 'flex gap-1' }, [
        h(Button, { size: 'sm', variant: 'default', onClick: () => openApprove(app) }, () => 'Approve'),
        h(Button, { size: 'sm', variant: 'outline', onClick: () => reject(app.id) }, () => 'Reject'),
      ])
    },
  },
]

const { table, globalFilter } = useDataTable({ data: rows as never, columns })

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

async function onSubmit(values: Record<string, unknown>) {
  saving.value = true
  try {
    await enrollmentApi.create(values)
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
    title="Enrollment"
    description="Review applications and place admitted students into class, stream, and house"
  >
    <template #actions>
      <Button @click="openCreate">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        New application
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading enrollment applications" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <DataTable
      v-else
      :table="table"
      :columns="columns"
      :global-filter="globalFilter"
      search-placeholder="Search applications…"
      @update:global-filter="globalFilter = $event"
    />
  </PageShell>

  <Dialog :open="approveOpen" @update:open="approveOpen = $event">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Approve and place student</DialogTitle>
        <DialogDescription>
          {{ approveTarget?.first_name }} {{ approveTarget?.surname }}
          · {{ approveTarget?.grade_applying_for }}
          · {{ approveTarget?.academic_year }}
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-2">
        <div class="space-y-2">
          <Label for="approve-stream">Stream (optional)</Label>
          <Select v-model="selectedStreamId">
            <SelectTrigger id="approve-stream">
              <SelectValue placeholder="Any stream" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">Any stream</SelectItem>
              <SelectItem v-for="s in streams" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="approve-class">Class</Label>
          <Select v-model="selectedClassId">
            <SelectTrigger id="approve-class">
              <SelectValue placeholder="Select class" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="c in filteredClasses" :key="c.id" :value="String(c.id)">
                {{ c.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p class="text-xs text-muted-foreground">
            Homeroom: {{ homeroomLabel }} · {{ capacityLabel }}
          </p>
        </div>

        <div class="space-y-2">
          <Label for="approve-house">House (optional)</Label>
          <Select v-model="selectedHouseId">
            <SelectTrigger id="approve-house">
              <SelectValue placeholder="No house" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">No house</SelectItem>
              <SelectItem v-for="h in houses" :key="h.id" :value="String(h.id)">{{ h.name }}</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" :disabled="approving" @click="approveOpen = false">Cancel</Button>
        <Button :disabled="approving || !selectedClassId" @click="confirmApprove">
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
