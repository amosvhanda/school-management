<script setup lang="ts">
import { computed, h, onMounted, ref, nextTick } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import {
  Calendar,
  CheckCircle2,
  ClipboardList,
  MoreHorizontal,
  Pencil,
  Plus,
  Send,
  Trash2,
  Upload,
} from 'lucide-vue-next'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import ListFiltersBar from '@/components/data-table/ListFiltersBar.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import ExamResultsSheet from '@/modules/academics/components/ExamResultsSheet.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useToast } from '@/components/ui/toast/use-toast'
import { useAuth } from '@/composables/useAuth'
import { useFormSheetLoader } from '@/composables/useFormSheetLoader'
import { useListFilters } from '@/composables/useListFilters'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime, formatSchedule } from '@/lib/format'
import {
  examCreateDefaults,
  examFormFields,
  examFormSchema,
  mapExamFormToPayload,
  mapExamRowToFormValues,
} from '@/modules/academics/exam-form'
import type { ListFilterSchema } from '@/modules/shared/list-filters'
import { academicsApi } from '@/services/api.service'
import { moduleEndpoints } from '@/services'

interface ExamRow {
  id: number
  name?: string
  exam_date?: string
  start_time?: string | null
  end_time?: string | null
  academic_year?: string
  total_marks?: number
  is_published?: boolean
  results_approved_at?: string | null
  exam_results_count?: number
  term?: { name?: string }
  grade_level?: { name?: string }
  subject?: { name?: string }
}

function examScheduleLabel(exam: ExamRow): string {
  return formatSchedule(exam.exam_date, exam.start_time, exam.end_time)
}

const { toast } = useToast()
const { checkCapability } = useAuth()
const canManageExams = computed(() => checkCapability('canManageExaminations'))
const canEnterResults = computed(() => checkCapability('canEnterExamResults'))
const canShowActions = computed(() => canManageExams.value || canEnterResults.value)
const canRemove = computed(() => checkCapability('canManageTeachers'))

const rows = ref<ExamRow[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const saving = ref(false)
const editing = ref<ExamRow | null>(null)
const deleteTarget = ref<ExamRow | null>(null)
const publishTarget = ref<ExamRow | null>(null)
const approveTarget = ref<ExamRow | null>(null)
const resultsExamId = ref<number | null>(null)
const resultsSheetOpen = ref(false)
const formResetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const examFilters: ListFilterSchema[] = [
  {
    key: 'term_id',
    label: 'Term',
    type: 'relation',
    placeholder: 'All terms',
    relation: { endpoint: moduleEndpoints.terms, moduleLabel: 'term' },
  },
  {
    key: 'grade_level_id',
    label: 'Grade',
    type: 'relation',
    placeholder: 'All grades',
    relation: { endpoint: moduleEndpoints.gradeLevels, moduleLabel: 'grade level' },
  },
  {
    key: 'subject_id',
    label: 'Subject',
    type: 'relation',
    placeholder: 'All subjects',
    relation: { endpoint: moduleEndpoints.subjects, moduleLabel: 'subject' },
  },
]

const { values: filterValues, activeCount, buildParams, clearAll } = useListFilters(
  computed(() => examFilters),
  { onChange: () => load() },
)

const { formLoading, prepareCreate, prepareEdit } = useFormSheetLoader(() => ({
  endpoint: moduleEndpoints.exams,
  formFields: examFormFields,
  setFormValues: (values) => { formResetValues.value = values },
  mapRowToValues: mapExamRowToFormValues,
  createDefaults: examCreateDefaults,
}))

const kpis = computed(() => {
  const today = new Date().toISOString().slice(0, 10)
  return {
    total: rows.value.length,
    published: rows.value.filter((e) => e.is_published).length,
    pendingApproval: rows.value.filter((e) => e.exam_results_count && !e.results_approved_at).length,
    upcoming: rows.value.filter((e) => String(e.exam_date ?? '').slice(0, 10) >= today).length,
  }
})

function examStatus(exam: ExamRow): { label: string; variant: 'default' | 'secondary' | 'outline' } {
  if (exam.is_published) return { label: 'Published', variant: 'default' }
  if (exam.results_approved_at) return { label: 'Approved', variant: 'secondary' }
  if (exam.exam_results_count) return { label: 'Results entered', variant: 'outline' }
  return { label: 'Scheduled', variant: 'outline' }
}

// Cell renderers are required — DataTable slots are optional overrides.
const columns: ColumnDef<ExamRow>[] = [
  {
    accessorKey: 'name',
    header: 'Exam',
    cell: ({ row }) => h('span', { class: 'font-medium text-sm text-foreground' }, row.original.name || '—'),
  },
  {
    id: 'schedule',
    header: 'Schedule',
    accessorFn: (row) => row.exam_date,
    cell: ({ row }) => {
      const exam = row.original
      const children = [
        h('p', { class: 'text-sm text-foreground' }, examScheduleLabel(exam)),
      ]
      if (exam.results_approved_at) {
        children.push(
          h(
            'p',
            {
              class: 'text-xs text-muted-foreground',
              title: formatDateTime(exam.results_approved_at),
            },
            `Approved ${formatDateTime(exam.results_approved_at)}`,
          ),
        )
      }
      return h('div', { class: 'space-y-0.5' }, children)
    },
  },
  {
    id: 'term',
    header: 'Term',
    accessorFn: (row) => row.term?.name,
    cell: ({ row }) => h('span', { class: 'text-sm text-foreground/90' }, row.original.term?.name || '—'),
  },
  {
    id: 'grade_level',
    header: 'Grade',
    accessorFn: (row) => row.grade_level?.name,
    cell: ({ row }) => h('span', { class: 'text-sm text-foreground/90' }, row.original.grade_level?.name || '—'),
  },
  {
    id: 'subject',
    header: 'Subject',
    accessorFn: (row) => row.subject?.name,
    cell: ({ row }) => h('span', { class: 'text-sm text-foreground/90' }, row.original.subject?.name || '—'),
  },
  {
    id: 'marks',
    header: 'Marks',
    accessorFn: (row) => row.total_marks,
    cell: ({ row }) => h('span', { class: 'text-sm font-medium' }, String(row.original.total_marks ?? '—')),
  },
  { id: 'results', header: 'Results' },
  { id: 'status', header: 'Status' },
  { id: 'actions', header: '' },
]

const { table, globalFilter } = useDataTable({ data: rows as never, columns })

async function load() {
  loading.value = true
  error.value = null
  try {
    rows.value = await academicsApi.exams.list(buildParams()) as ExamRow[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load exams')
  } finally {
    loading.value = false
  }
}

async function openCreate() {
  editing.value = null
  formResetValues.value = examCreateDefaults()
  sheetOpen.value = true
  await nextTick()
  await prepareCreate()
}

async function openEdit(exam: ExamRow) {
  editing.value = exam
  sheetOpen.value = true
  await nextTick()
  const record = await prepareEdit(exam as unknown as Record<string, unknown>)
  editing.value = record as unknown as ExamRow
}

function openResults(id: number) {
  resultsExamId.value = id
  resultsSheetOpen.value = true
}

async function confirmPublish() {
  if (!publishTarget.value) return
  try {
    await academicsApi.exams.publish(publishTarget.value.id, { notify_parents: true })
    toast({
      title: 'Exam published successfully',
      description: 'Results are now visible within the student and parent portal dashboards.',
    })
    publishTarget.value = null
    await load()
  } catch (err) {
    toast({
      title: 'Publish failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  }
}

async function confirmApprove() {
  if (!approveTarget.value) return
  try {
    await academicsApi.exams.approveResults(approveTarget.value.id)
    toast({
      title: 'Results approved successfully',
      description: 'Marks are successfully locked for moderation analysis.',
    })
    approveTarget.value = null
    await load()
  } catch (err) {
    toast({
      title: 'Approve failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  try {
    await academicsApi.exams.remove(deleteTarget.value.id)
    toast({ title: 'Examination record purged successfully' })
    deleteTarget.value = null
    await load()
  } catch (err) {
    toast({
      title: 'Delete failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  }
}

async function onSubmit(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = mapExamFormToPayload(values)
    if (editing.value) {
      await academicsApi.exams.update(editing.value.id, payload)
      toast({ title: 'Examination parameters modified successfully' })
    } else {
      await academicsApi.exams.create(payload)
      toast({ title: 'New examination scheduled successfully' })
    }
    sheetOpen.value = false
    await load()
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast({
      title: editing.value ? 'Update failed' : 'Scheduling failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Examinations"
    :description="canManageExams
      ? 'Schedule exams, enter marks, approve results, and publish to parents'
      : 'Enter marks for exams in subjects you teach'"
  >
    <template #actions>
      <Button v-if="canManageExams" @click="openCreate">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        Schedule exam
      </Button>
    </template>

    <PageLoader v-if="loading && !rows.length" label="Loading examinations index…" />
    <ErrorState v-else-if="error && !rows.length" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Total exams"
          :value="String(kpis.total)"
          subtitle="Scheduled in system"
          :icon="Calendar"
        />
        <KpiCard
          title="Upcoming"
          :value="String(kpis.upcoming)"
          subtitle="Exams on or after today"
          :icon="Calendar"
          accent="warning"
        />
        <KpiCard
          title="Pending approval"
          :value="String(kpis.pendingApproval)"
          subtitle="Results awaiting sign-off"
          :icon="CheckCircle2"
        />
        <KpiCard
          title="Published"
          :value="String(kpis.published)"
          subtitle="Visible to parents"
          :icon="Upload"
          accent="success"
        />
      </div>

      <DataTable
        :table="table"
        :columns="columns"
        :global-filter="globalFilter"
        search-placeholder="Search exams…"
        @update:global-filter="globalFilter = $event"
      >
        <template #filters>
          <ListFiltersBar
            v-model="filterValues"
            :filters="examFilters"
            :active-count="activeCount"
            @clear="clearAll"
          />
        </template>

        <!-- Declarative slot cell handlers mapping down row context natively -->
        <template #cell-name="{ row }">
          <span class="font-medium text-sm text-foreground">{{ row.original.name || '—' }}</span>
        </template>

        <template #cell-schedule="{ row }">
          <div class="space-y-0.5">
            <p class="text-sm text-foreground">{{ examScheduleLabel(row.original) }}</p>
            <p
              v-if="row.original.results_approved_at"
              class="text-xs text-muted-foreground"
              :title="formatDateTime(row.original.results_approved_at)"
            >
              Approved {{ formatDateTime(row.original.results_approved_at) }}
            </p>
          </div>
        </template>

        <template #cell-term="{ row }">
          <span class="text-sm text-foreground/90">{{ row.original.term?.name || '—' }}</span>
        </template>

        <template #cell-grade_level="{ row }">
          <span class="text-sm text-foreground/90">{{ row.original.grade_level?.name || '—' }}</span>
        </template>

        <template #cell-subject="{ row }">
          <span class="text-sm text-foreground/90">{{ row.original.subject?.name || '—' }}</span>
        </template>

        <template #cell-marks="{ row }">
          <span class="text-sm font-medium">{{ row.original.total_marks ?? '—' }}</span>
        </template>

        <template #cell-results="{ row }">
          <Badge v-if="row.original.exam_results_count" variant="secondary" class="font-normal text-xs">
            {{ row.original.exam_results_count }} entered
          </Badge>
          <span v-else class="text-xs text-muted-foreground/70 pl-2">—</span>
        </template>

        <template #cell-status="{ row }">
          <Badge :variant="examStatus(row.original).variant" class="font-normal text-xs capitalize">
            {{ examStatus(row.original).label }}
          </Badge>
        </template>

        <template #cell-actions="{ row }">
          <div v-if="canShowActions" class="flex justify-end items-center pr-2">
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="h-8 w-8">
                  <MoreHorizontal class="h-4 w-4" />
                  <span class="sr-only">Open action options</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-[180px]">
                <DropdownMenuItem v-if="canManageExams" @click="openEdit(row.original)">
                  <Pencil class="mr-2 h-4 w-4" />
                  Edit exam details
                </DropdownMenuItem>

                <DropdownMenuItem
                  v-if="canManageExams || canEnterResults"
                  :disabled="Boolean(row.original.is_published || row.original.results_approved_at)"
                  @click="openResults(row.original.id)"
                >
                  <ClipboardList class="mr-2 h-4 w-4" />
                  Enter marks matrix
                </DropdownMenuItem>

                <DropdownMenuItem
                  v-if="canManageExams && row.original.exam_results_count && !row.original.results_approved_at"
                  @click="approveTarget = row.original"
                >
                  <CheckCircle2 class="mr-2 h-4 w-4" />
                  Approve marks
                </DropdownMenuItem>

                <DropdownMenuItem
                  v-if="canManageExams && !row.original.is_published"
                  @click="publishTarget = row.original"
                >
                  <Send class="mr-2 h-4 w-4" />
                  Publish to parents
                </DropdownMenuItem>

                <template v-if="canRemove">
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    class="text-destructive focus:text-destructive-foreground focus:bg-destructive"
                    @click="deleteTarget = row.original"
                  >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Purge exam
                  </DropdownMenuItem>
                </template>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </template>
      </DataTable>
    </template>
  </PageShell>

  <FormSheet
    ref="formSheetRef"
    v-model:open="sheetOpen"
    :title="editing ? 'Edit examination' : 'Schedule examination'"
    :description="editing ? 'Update exam details and marking criteria.' : 'Set term, grade, subject, and schedule for a new exam.'"
    :fields="examFormFields"
    :schema="examFormSchema"
    :reset-values="formResetValues"
    :form-key="editing ? `exam-edit-${editing.id}` : 'exam-create'"
    :form-loading="formLoading"
    :saving="saving"
    :save-label="editing ? 'Save changes' : 'Schedule exam'"
    size="lg"
    @submit="onSubmit"
  />

  <ExamResultsSheet
    v-model:open="resultsSheetOpen"
    :exam-id="resultsExamId"
    @update:open="(v) => { if (!v) { resultsExamId = null; load() } }"
  />

  <!-- Modal Dialog Components -->
  <Dialog :open="!!deleteTarget" @update:open="(v) => !v && (deleteTarget = null)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Purge examination data tier?</DialogTitle>
        <DialogDescription>
          This operation permanently discards <strong class="text-foreground">“{{ deleteTarget?.name }}”</strong> alongside all currently associated marks logs. This setup cannot be undone.
        </DialogDescription>
      </DialogHeader>
      <DialogFooter class="gap-2 sm:gap-0">
        <Button variant="outline" @click="deleteTarget = null">Cancel</Button>
        <Button variant="destructive" @click="confirmDelete">Confirm Purge</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <Dialog :open="!!approveTarget" @update:open="(v) => !v && (approveTarget = null)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Lock & Approve results?</DialogTitle>
        <DialogDescription>
          Approving flags performance logs for <strong class="text-foreground">“{{ approveTarget?.name }}”</strong> as signed off. This step locks data layers preparing them for publication pathways.
        </DialogDescription>
      </DialogHeader>
      <DialogFooter class="gap-2 sm:gap-0">
        <Button variant="outline" @click="approveTarget = null">Cancel</Button>
        <Button @click="confirmApprove">Approve Results</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <Dialog :open="!!publishTarget" @update:open="(v) => !v && (publishTarget = null)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Publish results to portal feeds?</DialogTitle>
        <DialogDescription>
          This schedules an update notifying family channels that metric feeds for <strong class="text-foreground">“{{ publishTarget?.name }}”</strong> are available. Marks must hold verified authorization parameters before final release.
        </DialogDescription>
      </DialogHeader>
      <DialogFooter class="gap-2 sm:gap-0">
        <Button variant="outline" @click="publishTarget = null">Cancel</Button>
        <Button @click="confirmPublish">Publish Feed</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
