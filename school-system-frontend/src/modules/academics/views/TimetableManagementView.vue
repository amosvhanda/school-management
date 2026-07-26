<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { z } from 'zod'
import {
  CalendarDays,
  MapPin,
  Pencil,
  Plus,
  Printer,
  Save,
  Sparkles,
  Trash2,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import PageShell from '@/components/layout/PageShell.vue'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { Badge } from '@/components/ui/badge'
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import { getErrorMessage } from '@/lib/api-response'
import { cn } from '@/lib/utils'
import { academicsApi } from '@/services/api.service'
import { moduleEndpoints } from '@/services'
import { preloadRelationFields } from '@/lib/relation-options'

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as const

type GridRow =
  | { kind: 'period'; label: string; start: string; end: string }
  | { kind: 'break'; label: string; start: string; end: string }

/** Zimbabwe-style periods with break rows matching the generator gaps. */
const GRID_ROWS: GridRow[] = [
  { kind: 'period', label: 'Period 1', start: '07:30', end: '08:15' },
  { kind: 'period', label: 'Period 2', start: '08:15', end: '09:00' },
  { kind: 'break', label: 'Break', start: '09:00', end: '09:15' },
  { kind: 'period', label: 'Period 3', start: '09:15', end: '10:00' },
  { kind: 'period', label: 'Period 4', start: '10:00', end: '10:45' },
  { kind: 'break', label: 'Break', start: '10:45', end: '11:00' },
  { kind: 'period', label: 'Period 5', start: '11:00', end: '11:45' },
  { kind: 'period', label: 'Period 6', start: '11:45', end: '12:30' },
]

const SUBJECT_COLORS = [
  'bg-sky-100 text-sky-950 border-sky-200 dark:bg-sky-950/50 dark:text-sky-100 dark:border-sky-800',
  'bg-violet-100 text-violet-950 border-violet-200 dark:bg-violet-950/50 dark:text-violet-100 dark:border-violet-800',
  'bg-emerald-100 text-emerald-950 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-100 dark:border-emerald-800',
  'bg-rose-100 text-rose-950 border-rose-200 dark:bg-rose-950/50 dark:text-rose-100 dark:border-rose-800',
  'bg-amber-100 text-amber-950 border-amber-200 dark:bg-amber-950/50 dark:text-amber-100 dark:border-amber-800',
  'bg-orange-100 text-orange-950 border-orange-200 dark:bg-orange-950/50 dark:text-orange-100 dark:border-orange-800',
  'bg-teal-100 text-teal-950 border-teal-200 dark:bg-teal-950/50 dark:text-teal-100 dark:border-teal-800',
  'bg-indigo-100 text-indigo-950 border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-100 dark:border-indigo-800',
]

interface ClassOption {
  id: number
  name: string
}

interface TermOption {
  id: number
  name?: string
  academic_year?: string | number
}

interface TimetableSlot {
  id: number
  day?: string
  start_time?: string
  end_time?: string
  subject_id?: number | null
  teacher_id?: number | null
  room_id?: number | null
  class_id?: number
  subject?: string | { id?: number; name?: string }
  room?: string | { id?: number; name?: string } | null
  teacher?: { id?: number; name?: string; first_name?: string; last_name?: string } | null
}

const loading = ref(true)
const gridLoading = ref(false)
const saving = ref(false)
const generating = ref(false)
const error = ref<string | null>(null)
const classes = ref<ClassOption[]>([])
const terms = ref<TermOption[]>([])
const selectedClassId = ref('')
const selectedYear = ref(String(new Date().getFullYear()))
const selectedTermId = ref('')
const slots = ref<TimetableSlot[]>([])
const editMode = ref(false)
const sheetOpen = ref(false)
const sheetSaving = ref(false)
const editingSlot = ref<TimetableSlot | null>(null)
const draftDay = ref<string>('Monday')
const draftStart = ref('07:30')
const draftEnd = ref('08:15')
const deleteTarget = ref<TimetableSlot | null>(null)
const formKey = ref(0)
const formResetValues = ref<Record<string, unknown>>({})

const selectedClass = computed(() =>
  classes.value.find((c) => String(c.id) === selectedClassId.value),
)

const yearOptions = computed(() => {
  const current = new Date().getFullYear()
  const years = new Set<string>([String(current), String(current - 1), String(current + 1)])
  for (const term of terms.value) {
    if (term.academic_year != null) years.add(String(term.academic_year))
  }
  return [...years].sort()
})

const slotFields = computed<FormFieldSchema[]>(() => [
  {
    name: 'subject_id',
    label: 'Subject',
    type: 'relation',
    required: true,
    placeholder: 'Select subject',
    relation: { endpoint: moduleEndpoints.subjects, moduleLabel: 'subject' },
  },
  {
    name: 'teacher_id',
    label: 'Teacher',
    type: 'relation',
    placeholder: 'Optional teacher',
    relation: { endpoint: moduleEndpoints.teachers },
  },
  {
    name: 'room_id',
    label: 'Room',
    type: 'relation',
    placeholder: 'Optional room',
    relation: { endpoint: moduleEndpoints.rooms },
  },
])

const slotSchema = z.object({
  subject_id: z.string().min(1, 'Subject is required'),
  teacher_id: z.string().optional().or(z.literal('')),
  room_id: z.string().optional().or(z.literal('')),
})

const gridMap = computed(() => {
  const map = new Map<string, TimetableSlot>()
  for (const slot of slots.value) {
    const day = slot.day ?? ''
    const start = normalizeTime(slot.start_time)
    const end = normalizeTime(slot.end_time)
    if (!day || !start || !end) continue
    map.set(`${day}|${start}|${end}`, slot)
  }
  return map
})

function normalizeTime(value?: string | null) {
  if (!value) return ''
  const match = String(value).match(/(\d{2}:\d{2})/)
  return match?.[1] ?? ''
}

function cell(day: string, start: string, end: string) {
  return gridMap.value.get(`${day}|${start}|${end}`) ?? null
}

function subjectLabel(slot: TimetableSlot) {
  if (typeof slot.subject === 'string' && slot.subject.trim()) return slot.subject
  if (slot.subject && typeof slot.subject === 'object' && slot.subject.name) return slot.subject.name
  return 'Lesson'
}

function teacherLabel(slot: TimetableSlot) {
  const t = slot.teacher
  if (!t) return '—'
  if (t.name?.trim()) return t.name
  const parts = [t.first_name, t.last_name].filter(Boolean)
  return parts.length ? parts.join(' ') : '—'
}

function roomLabel(slot: TimetableSlot) {
  if (typeof slot.room === 'string' && slot.room.trim()) return slot.room
  if (slot.room && typeof slot.room === 'object' && slot.room.name) return slot.room.name
  return '—'
}

function subjectColor(name: string) {
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0
  return SUBJECT_COLORS[hash % SUBJECT_COLORS.length]
}

async function loadSetup() {
  loading.value = true
  error.value = null
  try {
    const [classList, termList] = await Promise.all([
      academicsApi.classes.list() as Promise<ClassOption[]>,
      academicsApi.terms.list().catch(() => []) as Promise<TermOption[]>,
    ])
    classes.value = classList
    terms.value = termList
    if (classes.value.length && !selectedClassId.value) {
      selectedClassId.value = String(classes.value[0].id)
    }
    if (terms.value.length && !selectedTermId.value) {
      selectedTermId.value = String(terms.value[0].id)
      if (terms.value[0].academic_year != null) {
        selectedYear.value = String(terms.value[0].academic_year)
      }
    } else if (!terms.value.length) {
      selectedTermId.value = 'term-1'
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load timetable setup')
  } finally {
    loading.value = false
  }
}

async function loadGrid() {
  if (!selectedClassId.value) {
    slots.value = []
    return
  }
  gridLoading.value = true
  try {
    slots.value = (await academicsApi.timetable.list({
      class_id: selectedClassId.value,
      all: true,
    })) as TimetableSlot[]
  } catch (err) {
    toast.error('Could not load timetable', { description: getErrorMessage(err) })
    slots.value = []
  } finally {
    gridLoading.value = false
  }
}

async function openCreate(day: string, start: string, end: string) {
  if (!editMode.value || !selectedClassId.value) return
  editingSlot.value = null
  draftDay.value = day
  draftStart.value = start
  draftEnd.value = end
  formResetValues.value = { subject_id: '', teacher_id: '', room_id: '' }
  await preloadRelationFields(slotFields.value)
  formKey.value += 1
  sheetOpen.value = true
}

async function openEdit(slot: TimetableSlot) {
  if (!editMode.value) return
  editingSlot.value = slot
  draftDay.value = slot.day ?? 'Monday'
  draftStart.value = normalizeTime(slot.start_time)
  draftEnd.value = normalizeTime(slot.end_time)
  formResetValues.value = {
    subject_id: slot.subject_id != null ? String(slot.subject_id) : '',
    teacher_id: slot.teacher_id != null ? String(slot.teacher_id) : '',
    room_id: slot.room_id != null ? String(slot.room_id) : '',
  }
  await preloadRelationFields(slotFields.value)
  formKey.value += 1
  sheetOpen.value = true
}

async function onSlotSubmit(values: Record<string, unknown>) {
  if (!selectedClassId.value) return
  sheetSaving.value = true
  try {
    const payload: Record<string, unknown> = {
      class_id: Number(selectedClassId.value),
      subject_id: Number(values.subject_id),
      teacher_id: values.teacher_id ? Number(values.teacher_id) : null,
      room_id: values.room_id ? Number(values.room_id) : null,
      day: draftDay.value,
      start_time: draftStart.value,
      end_time: draftEnd.value,
    }

    if (editingSlot.value) {
      await academicsApi.timetable.update(editingSlot.value.id, payload)
      toast.success('Lesson updated')
    } else {
      await academicsApi.timetable.create(payload)
      toast.success('Lesson added')
    }
    sheetOpen.value = false
    await loadGrid()
  } catch (err) {
    toast.error(editingSlot.value ? 'Update failed' : 'Could not add lesson', {
      description: getErrorMessage(err),
    })
  } finally {
    sheetSaving.value = false
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  try {
    await academicsApi.timetable.remove(deleteTarget.value.id)
    toast.success('Lesson removed')
    deleteTarget.value = null
    sheetOpen.value = false
    await loadGrid()
  } catch (err) {
    toast.error('Delete failed', { description: getErrorMessage(err) })
  }
}

async function generateTimetable(replace = false) {
  if (!selectedClassId.value) return
  generating.value = true
  try {
    await academicsApi.timetable.generate({
      class_id: Number(selectedClassId.value),
      replace_existing: replace,
    })
    toast.success(replace ? 'Timetable regenerated' : 'Timetable generated')
    await loadGrid()
  } catch (err) {
    toast.error('Generate failed', { description: getErrorMessage(err) })
  } finally {
    generating.value = false
  }
}

async function saveTimetable() {
  if (!selectedClassId.value) return
  if (!slots.value.length) {
    await generateTimetable(false)
    return
  }
  saving.value = true
  try {
    // Slot create/update/delete already persist immediately; refresh confirms server state.
    await loadGrid()
    editMode.value = false
    toast.success('Timetable confirmed', {
      description: `${slots.value.length} lesson(s) synced for ${selectedClass.value?.name ?? 'this class'}.`,
    })
  } catch (err) {
    toast.error('Could not confirm timetable', { description: getErrorMessage(err) })
  } finally {
    saving.value = false
  }
}

function printTimetable() {
  window.print()
}

function onCellActivate(day: string, row: Extract<GridRow, { kind: 'period' }>) {
  if (!editMode.value) return
  const existing = cell(day, row.start, row.end)
  if (existing) void openEdit(existing)
  else void openCreate(day, row.start, row.end)
}

watch(selectedClassId, () => {
  void loadGrid()
})

watch(selectedTermId, (id) => {
  const term = terms.value.find((t) => String(t.id) === id)
  if (term?.academic_year != null) selectedYear.value = String(term.academic_year)
})

onMounted(async () => {
  await loadSetup()
  await loadGrid()
})
</script>

<template>
  <PageShell
    title="Timetable Management"
    description="Create and manage class timetables"
    max-width="wide"
  >
    <template #actions>
      <Button
        variant="outline"
        :class="editMode ? 'border-foreground bg-foreground text-background hover:bg-foreground/90' : ''"
        @click="editMode = !editMode"
      >
        <Pencil class="mr-2 size-4" aria-hidden="true" />
        {{ editMode ? 'Editing' : 'Edit Mode' }}
      </Button>
      <Button variant="outline" :disabled="!slots.length" @click="printTimetable">
        <Printer class="mr-2 size-4" aria-hidden="true" />
        Print
      </Button>
      <Button
        variant="outline"
        :disabled="generating || !selectedClassId"
        :aria-busy="generating"
        @click="generateTimetable(slots.length > 0)"
      >
        <Sparkles class="mr-2 size-4" aria-hidden="true" />
        {{ generating ? 'Generating…' : slots.length ? 'Regenerate' : 'Auto-generate' }}
      </Button>
      <Button
        :disabled="saving || !selectedClassId"
        :aria-busy="saving"
        @click="saveTimetable"
      >
        <Save class="mr-2 size-4" aria-hidden="true" />
        {{ saving ? 'Saving…' : 'Save Timetable' }}
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading timetable workspace…" />
    <ErrorState v-else-if="error" :description="error" @retry="loadSetup" />

    <div v-else class="space-y-6">
      <p
        v-if="editMode"
        class="rounded-xl border border-border/60 bg-muted/40 px-4 py-3 text-sm text-muted-foreground"
        role="status"
      >
        Edit mode is on — click an empty cell to add a lesson, or an existing lesson to change it.
      </p>

      <Card class="border-border/70 shadow-sm print:shadow-none">
        <CardHeader class="pb-4">
          <CardTitle class="text-base">Timetable Setup</CardTitle>
          <CardDescription>
            Class filters the live weekly grid. Year and term are for reference (timetables are weekly, not term-versioned yet).
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 sm:grid-cols-3">
            <div class="space-y-2">
              <Label for="tt-class">Select Class</Label>
              <Select v-model="selectedClassId">
                <SelectTrigger id="tt-class" class="h-10">
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
              <Label for="tt-year">Academic Year</Label>
              <Select v-model="selectedYear">
                <SelectTrigger id="tt-year" class="h-10">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="year in yearOptions" :key="year" :value="year">
                    {{ year }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label for="tt-term">Term</Label>
              <Select v-model="selectedTermId">
                <SelectTrigger id="tt-term" class="h-10">
                  <SelectValue placeholder="Select term" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="term in terms" :key="term.id" :value="String(term.id)">
                    {{ term.name ?? `Term ${term.id}` }}
                  </SelectItem>
                  <SelectItem v-if="!terms.length" value="term-1">Term 1</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="overflow-hidden border-border/70 shadow-sm print:shadow-none">
        <CardHeader class="border-b border-border/60 pb-4">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
              <CardTitle class="flex items-center gap-2 text-base">
                <CalendarDays class="size-4 text-muted-foreground" aria-hidden="true" />
                Weekly Timetable
                <span v-if="selectedClass">— {{ selectedClass.name }}</span>
              </CardTitle>
              <CardDescription>
                {{ selectedYear }}
                <span v-if="terms.find((t) => String(t.id) === selectedTermId)">
                  · {{ terms.find((t) => String(t.id) === selectedTermId)?.name }}
                </span>
              </CardDescription>
            </div>
            <Badge variant="secondary">{{ slots.length }} lessons</Badge>
          </div>
        </CardHeader>

        <CardContent class="p-0">
          <PageLoader v-if="gridLoading" class="py-16" label="Loading weekly grid…" />

          <EmptyState
            v-else-if="!selectedClassId"
            title="Select a class"
            description="Choose a class above to open its weekly timetable."
          />

          <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[56rem] border-collapse text-sm" aria-label="Weekly timetable">
              <thead>
                <tr class="border-b border-border/60 bg-muted/40">
                  <th
                    scope="col"
                    class="sticky left-0 z-10 w-36 bg-muted/40 px-3 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                  >
                    Time
                  </th>
                  <th
                    v-for="day in DAYS"
                    :key="day"
                    scope="col"
                    class="px-3 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                  >
                    {{ day }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in GRID_ROWS"
                  :key="`${row.kind}-${row.start}-${row.end}`"
                  class="border-b border-border/40 last:border-0"
                >
                  <th
                    scope="row"
                    class="sticky left-0 z-10 whitespace-nowrap bg-background px-3 py-3 text-left align-top"
                  >
                    <div class="rounded-lg border border-border/60 bg-muted/30 px-3 py-2">
                      <p class="text-xs font-semibold text-foreground">
                        {{ row.kind === 'period' ? row.label : 'Break' }}
                      </p>
                      <p class="text-[11px] text-muted-foreground">
                        {{ row.start }} – {{ row.end }}
                      </p>
                    </div>
                  </th>

                  <template v-if="row.kind === 'break'">
                    <td :colspan="DAYS.length" class="bg-muted/20 px-3 py-3 text-center align-middle">
                      <span class="text-sm font-medium text-muted-foreground">{{ row.label }}</span>
                    </td>
                  </template>

                  <template v-else>
                    <td
                      v-for="day in DAYS"
                      :key="`${day}-${row.start}`"
                      class="align-top px-2 py-2"
                    >
                      <div
                        v-if="cell(day, row.start, row.end)"
                        class="relative"
                      >
                        <button
                          type="button"
                          :class="cn(
                            'w-full rounded-xl border px-3 py-2.5 text-left transition-shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                            subjectColor(subjectLabel(cell(day, row.start, row.end)!)),
                            editMode ? 'cursor-pointer hover:shadow-sm' : 'cursor-default',
                          )"
                          :disabled="!editMode"
                          :aria-label="`${subjectLabel(cell(day, row.start, row.end)!)} on ${day}`"
                          @click="onCellActivate(day, row)"
                        >
                          <p class="text-sm font-semibold leading-tight">
                            {{ subjectLabel(cell(day, row.start, row.end)!) }}
                          </p>
                          <p class="mt-1 text-xs opacity-80">
                            {{ teacherLabel(cell(day, row.start, row.end)!) }}
                          </p>
                          <p class="mt-1.5 flex items-center gap-1 text-[11px] opacity-70">
                            <MapPin class="size-3 shrink-0" aria-hidden="true" />
                            {{ roomLabel(cell(day, row.start, row.end)!) }}
                          </p>
                        </button>
                        <Button
                          v-if="editMode"
                          type="button"
                          size="icon"
                          variant="secondary"
                          class="absolute top-1 right-1 size-7"
                          :aria-label="`Delete ${subjectLabel(cell(day, row.start, row.end)!)}`"
                          @click.stop="deleteTarget = cell(day, row.start, row.end)"
                        >
                          <Trash2 class="size-3.5" aria-hidden="true" />
                        </Button>
                      </div>

                      <button
                        v-else-if="editMode"
                        type="button"
                        class="flex h-full min-h-[4.5rem] w-full items-center justify-center rounded-xl border border-dashed border-border/70 bg-muted/10 text-muted-foreground transition-colors hover:bg-muted/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :aria-label="`Add lesson on ${day} ${row.label}`"
                        @click="onCellActivate(day, row)"
                      >
                        <Plus class="size-4" aria-hidden="true" />
                        <span class="sr-only">Add lesson</span>
                      </button>

                      <div
                        v-else
                        class="min-h-[4.5rem] rounded-xl border border-transparent bg-transparent"
                        aria-hidden="true"
                      />
                    </td>
                  </template>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>

    <FormSheet
      v-model:open="sheetOpen"
      :title="editingSlot ? 'Edit lesson' : 'Add lesson'"
      :description="`${draftDay} · ${draftStart} – ${draftEnd}${selectedClass ? ` · ${selectedClass.name}` : ''}`"
      :fields="slotFields"
      :schema="slotSchema"
      :reset-values="formResetValues"
      :form-key="String(formKey)"
      :saving="sheetSaving"
      :save-label="editingSlot ? 'Save changes' : 'Add lesson'"
      @submit="onSlotSubmit"
    />

    <Dialog :open="!!deleteTarget" @update:open="(v) => !v && (deleteTarget = null)">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remove lesson?</DialogTitle>
          <DialogDescription>
            This removes the slot from the weekly timetable. You can add it again later.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="deleteTarget = null">Cancel</Button>
          <Button variant="destructive" @click="confirmDelete">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>

<style scoped>
@media print {
  :global(aside),
  :global(header),
  :global(nav) {
    display: none !important;
  }
}
</style>
