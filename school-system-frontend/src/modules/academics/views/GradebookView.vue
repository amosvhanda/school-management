<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Plus, Search, Users } from '@lucide/vue'
import { useRoute } from 'vue-router'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import {
  Table,
  TableBody,
  TableCell,
  TableEmpty,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useToast } from '@/components/ui/toast/use-toast'
import { cn } from '@/lib/utils'
import { getErrorMessage } from '@/lib/api-response'
import { academicsApi } from '@/services/api.service'
import { fetchList } from '@/services/dashboard.service'
import { moduleEndpoints } from '@/services'
import { useAuthStore } from '@/stores/auth.store'

interface ClassOption { id: number; name: string }
interface SubjectOption { id: number; name: string }
interface StudentOption { id: number; full_name?: string; student_number?: string }
interface TermOption { id: number; name: string; is_current?: boolean }
interface GradeRow {
  id: number
  subject?: string
  subject_id?: number
  score?: number
  total?: number
  grade?: string
  term?: string
  assessment_type?: string
  student?: { id?: number; full_name?: string }
  student_id?: number
}

const { toast } = useToast()
const authStore = useAuthStore()
const route = useRoute()

const classes = ref<ClassOption[]>([])
const subjects = ref<SubjectOption[]>([])
const students = ref<StudentOption[]>([])
const terms = ref<TermOption[]>([])
const selectedClass = ref<string>('')
const selectedSubjectFilter = ref('all')
const grades = ref<GradeRow[]>([])
const loading = ref(true)
const gradesLoading = ref(false)
const error = ref<string | null>(null)
const modalOpen = ref(false)
const saving = ref(false)
const searchQuery = ref('')
const selectedTypeFilter = ref('all')

const form = ref({
  student_id: '',
  subject_id: '',
  score: '',
  total: '100',
  term: '',
  assessment_type: 'test',
})

const isTeacher = computed(() => authStore.user?.role === 'teacher')
const teacherId = computed(() => authStore.user?.teacher_id)

const selectedClassName = computed(
  () => classes.value.find((c) => String(c.id) === selectedClass.value)?.name ?? 'Class',
)

const filteredGrades = computed(() => {
  return grades.value.filter((g) => {
    const name = String(g.student?.full_name ?? '').toLowerCase()
    const matchesSearch = !searchQuery.value || name.includes(searchQuery.value.toLowerCase())
    const matchesType = selectedTypeFilter.value === 'all' || g.assessment_type === selectedTypeFilter.value
    const matchesSubject =
      selectedSubjectFilter.value === 'all'
      || String(g.subject_id ?? '') === selectedSubjectFilter.value
      || String(g.subject ?? '').toLowerCase() === selectedSubjectFilter.value.toLowerCase()
    return matchesSearch && matchesType && matchesSubject
  })
})

/** Student-first marksheet rows: one learner, their recent marks. */
const marksheetRows = computed(() => {
  const byStudent = new Map<string, {
    key: string
    name: string
    number?: string
    marks: GradeRow[]
  }>()

  for (const student of students.value) {
    const key = String(student.id)
    byStudent.set(key, {
      key,
      name: student.full_name ?? (student.student_number ? `Student ${student.student_number}` : 'Student'),
      number: student.student_number,
      marks: [],
    })
  }

  for (const grade of filteredGrades.value) {
    const key = String(grade.student_id ?? grade.student?.id ?? grade.student?.full_name ?? grade.id)
    const existing = byStudent.get(key)
    if (existing) {
      existing.marks.push(grade)
    } else {
      byStudent.set(key, {
        key,
        name: grade.student?.full_name ?? 'Student',
        marks: [grade],
      })
    }
  }

  const q = searchQuery.value.trim().toLowerCase()
  return Array.from(byStudent.values())
    .filter((row) => !q || row.name.toLowerCase().includes(q) || String(row.number ?? '').toLowerCase().includes(q))
    .sort((a, b) => a.name.localeCompare(b.name))
})

const avgScore = computed(() => {
  const valid = filteredGrades.value.filter((g) => g.total && g.total > 0)
  if (!valid.length) return null
  const sum = valid.reduce((acc, g) => acc + ((g.score ?? 0) / (g.total ?? 1)) * 100, 0)
  return (sum / valid.length).toFixed(1)
})

const subjectOptionsForFilter = computed(() => {
  const fromCatalog = subjects.value.map((s) => ({ id: String(s.id), name: s.name }))
  if (fromCatalog.length) return fromCatalog
  const names = [...new Set(grades.value.map((g) => String(g.subject ?? '')).filter(Boolean))]
  return names.map((name) => ({ id: name, name }))
})

function scoreLabel(g: GradeRow): string {
  if (g.score == null) return '—'
  return g.total ? `${g.score}/${g.total}` : String(g.score)
}

function percent(g: GradeRow): number | null {
  if (g.score == null || !g.total || g.total <= 0) return null
  return Math.round(((g.score / g.total) * 100) * 10) / 10
}

async function loadClasses() {
  loading.value = true
  error.value = null

  const filterParams = isTeacher.value && teacherId.value
    ? { teacher_id: teacherId.value }
    : { all: true }

  try {
    const [classRows, subjectRows, termRows] = await Promise.all([
      fetchList<ClassOption>(moduleEndpoints.classes, filterParams),
      fetchList<SubjectOption>(moduleEndpoints.subjects, filterParams),
      fetchList<TermOption>(moduleEndpoints.terms, { all: true }),
    ])

    classes.value = classRows
    subjects.value = subjectRows
    terms.value = termRows

    const preferredClassId = route.query.class_id ? String(route.query.class_id) : ''
    if (preferredClassId && classes.value.some((c) => String(c.id) === preferredClassId)) {
      selectedClass.value = preferredClassId
    } else if (classes.value.length && !selectedClass.value) {
      selectedClass.value = String(classes.value[0].id)
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load gradebook'
  } finally {
    loading.value = false
  }
}

async function loadStudents() {
  if (!selectedClass.value) {
    students.value = []
    return
  }
  students.value = await fetchList<StudentOption>(moduleEndpoints.students, {
    class_id: selectedClass.value,
    limit: 500,
  })
}

async function loadGrades() {
  if (!selectedClass.value) return
  gradesLoading.value = true
  try {
    grades.value = await academicsApi.grades.byClass(selectedClass.value) as GradeRow[]
  } catch {
    grades.value = []
  } finally {
    gradesLoading.value = false
  }
}

function selectClass(id: string) {
  if (selectedClass.value === id) return
  selectedClass.value = id
  searchQuery.value = ''
  selectedSubjectFilter.value = 'all'
  selectedTypeFilter.value = 'all'
}

function openEntry(prefillStudentId?: number) {
  const activeTermId = terms.value.find((t) => t.is_current)?.id || terms.value[0]?.id || ''

  form.value = {
    student_id: prefillStudentId ? String(prefillStudentId) : '',
    subject_id: subjects.value[0] ? String(subjects.value[0].id) : '',
    score: '',
    total: '100',
    term: String(activeTermId),
    assessment_type: 'test',
  }
  modalOpen.value = true
}

async function saveGrade() {
  if (!selectedClass.value || !form.value.student_id || !form.value.subject_id) {
    toast({
      title: 'Missing fields',
      description: 'Select a student and subject before saving.',
      variant: 'destructive',
    })
    return
  }
  saving.value = true
  try {
    await academicsApi.grades.store({
      student_id: Number(form.value.student_id),
      class_id: Number(selectedClass.value),
      subject_id: Number(form.value.subject_id),
      score: Number(form.value.score),
      total: Number(form.value.total),
      term: form.value.term ? Number(form.value.term) : undefined,
      assessment_type: form.value.assessment_type,
    })
    toast({ title: 'Mark saved' })
    modalOpen.value = false
    await loadGrades()
  } catch (err) {
    toast({
      title: 'Could not save mark',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  } finally {
    saving.value = false
  }
}

watch(selectedClass, async () => {
  await loadStudents()
  await loadGrades()
})

onMounted(async () => {
  await loadClasses()
  await loadStudents()
  await loadGrades()
})
</script>

<template>
  <PageShell
    title="Gradebook"
    description="Class-first marks — pick a class, scan the marksheet, enter scores."
    max-width="wide"
  >
    <template #actions>
      <Button class="h-10 px-4" :disabled="!selectedClass" @click="openEntry()">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        Enter mark
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading gradebook…" />
    <ErrorState v-else-if="error" :description="error" @retry="loadClasses" />

    <div
      v-else-if="!classes.length"
      class="rounded-2xl border border-dashed border-border/70 px-6 py-16 text-center"
      role="status"
    >
      <Users class="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
      <p class="mt-3 text-sm font-medium text-foreground">No classes assigned</p>
      <p class="mt-1 text-sm text-muted-foreground">
        When you are assigned to classes, they will appear here for mark entry.
      </p>
    </div>

    <div
      v-else
      class="grid gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] xl:grid-cols-[15.5rem_minmax(0,1fr)]"
    >
      <!-- Class rail (desktop) -->
      <nav
        class="hidden lg:block"
        aria-label="Classes"
      >
        <div class="sticky top-20 space-y-1 rounded-2xl border border-border/60 bg-muted/20 p-2.5">
          <p class="px-2.5 pb-1 pt-0.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
            Classes
          </p>
          <button
            v-for="cls in classes"
            :key="cls.id"
            type="button"
            :class="cn(
              'flex w-full items-center rounded-xl px-2.5 py-2 text-left text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
              selectedClass === String(cls.id)
                ? 'bg-primary text-primary-foreground shadow-sm'
                : 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
            )"
            :aria-current="selectedClass === String(cls.id) ? 'page' : undefined"
            @click="selectClass(String(cls.id))"
          >
            <span class="truncate">{{ cls.name }}</span>
          </button>
        </div>
      </nav>

      <!-- Marksheet pane -->
      <section class="min-w-0 space-y-4" aria-label="Class marksheet">
        <!-- Mobile class select -->
        <div class="space-y-2 lg:hidden">
          <Label for="gradebook-class">Class</Label>
          <Select :model-value="selectedClass" @update:model-value="(v) => v && selectClass(String(v))">
            <SelectTrigger id="gradebook-class" class="w-full h-10">
              <SelectValue placeholder="Select class" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="cls in classes" :key="cls.id" :value="String(cls.id)">
                {{ cls.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <header class="flex flex-col gap-3 border-b border-border/60 pb-4 sm:flex-row sm:items-end sm:justify-between">
          <div class="min-w-0 space-y-1">
            <h2 class="font-heading text-lg font-semibold tracking-tight text-foreground">
              {{ selectedClassName }}
            </h2>
            <p class="text-sm text-muted-foreground">
              <span>{{ marksheetRows.length }} learner{{ marksheetRows.length === 1 ? '' : 's' }}</span>
              <span aria-hidden="true"> · </span>
              <span>{{ filteredGrades.length }} mark{{ filteredGrades.length === 1 ? '' : 's' }}</span>
              <template v-if="avgScore != null">
                <span aria-hidden="true"> · </span>
                <span>Avg {{ avgScore }}%</span>
              </template>
            </p>
          </div>

          <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="relative w-full sm:w-56">
              <Search
                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
              />
              <Input
                v-model="searchQuery"
                type="search"
                placeholder="Search learner…"
                class="h-9 pl-9 text-sm"
                aria-label="Search learner"
              />
            </div>
            <Select v-model="selectedSubjectFilter">
              <SelectTrigger class="h-9 w-full sm:w-40 text-sm" aria-label="Filter by subject">
                <SelectValue placeholder="All subjects" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All subjects</SelectItem>
                <SelectItem
                  v-for="s in subjectOptionsForFilter"
                  :key="s.id"
                  :value="s.id"
                >
                  {{ s.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Select v-model="selectedTypeFilter">
              <SelectTrigger class="h-9 w-full sm:w-36 text-sm" aria-label="Filter by assessment type">
                <SelectValue placeholder="All types" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All types</SelectItem>
                <SelectItem value="test">Test</SelectItem>
                <SelectItem value="assignment">Assignment</SelectItem>
                <SelectItem value="exam">Exam</SelectItem>
                <SelectItem value="project">Project</SelectItem>
                <SelectItem value="quiz">Quiz</SelectItem>
                <SelectItem value="other">Other</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </header>

        <PageLoader v-if="gradesLoading" label="Loading marks…" />

        <div
          v-else
          class="overflow-hidden rounded-2xl border border-border/60 bg-card"
        >
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow class="hover:bg-transparent">
                  <TableHead class="min-w-[10rem] sticky left-0 z-10 bg-card">Learner</TableHead>
                  <TableHead class="min-w-[14rem]">Marks</TableHead>
                  <TableHead class="w-28 text-right">
                    <span class="sr-only">Actions</span>
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="marksheetRows.length">
                  <TableRow
                    v-for="row in marksheetRows"
                    :key="row.key"
                    class="align-top"
                  >
                    <TableCell class="sticky left-0 z-10 bg-card font-medium">
                      <div class="min-w-0">
                        <p class="truncate text-foreground">{{ row.name }}</p>
                        <p v-if="row.number" class="truncate text-xs text-muted-foreground">
                          {{ row.number }}
                        </p>
                      </div>
                    </TableCell>
                    <TableCell>
                      <ul
                        v-if="row.marks.length"
                        class="flex flex-wrap gap-2"
                        role="list"
                      >
                        <li
                          v-for="mark in row.marks"
                          :key="mark.id"
                          class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-border/70 bg-muted/30 px-2 py-1"
                        >
                          <span class="truncate text-xs font-medium text-foreground">
                            {{ mark.subject ?? 'Subject' }}
                          </span>
                          <span class="font-mono text-xs tabular-nums text-foreground">
                            {{ scoreLabel(mark) }}
                          </span>
                          <Badge
                            v-if="mark.grade"
                            variant="outline"
                            class="h-5 px-1.5 text-[10px] font-medium"
                          >
                            {{ mark.grade }}
                          </Badge>
                          <span
                            v-if="percent(mark) != null"
                            class="text-[10px] text-muted-foreground tabular-nums"
                          >
                            {{ percent(mark) }}%
                          </span>
                          <span class="sr-only">
                            {{ mark.assessment_type ?? 'assessment' }}
                            <template v-if="mark.term">, {{ mark.term }}</template>
                          </span>
                        </li>
                      </ul>
                      <p v-else class="text-sm text-muted-foreground">No marks yet</p>
                    </TableCell>
                    <TableCell class="text-right">
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8"
                        @click="openEntry(Number(row.key) || undefined)"
                      >
                        Add
                      </Button>
                    </TableCell>
                  </TableRow>
                </template>
                <TableEmpty
                  v-else
                  :colspan="3"
                  title="No learners to show"
                  description="Adjust search or pick another class."
                />
              </TableBody>
            </Table>
          </div>
        </div>
      </section>
    </div>

    <Dialog v-model:open="modalOpen">
      <DialogContent class="flex max-h-[85vh] w-full max-w-md flex-col gap-0 overflow-hidden rounded-xl border p-0 shadow-lg">
        <DialogHeader class="shrink-0 space-y-1 border-b border-border/60 px-6 pb-4 pt-6">
          <DialogTitle class="text-base font-semibold tracking-tight">Enter mark</DialogTitle>
          <DialogDescription class="text-sm leading-relaxed">
            Record a score for {{ selectedClassName }}.
          </DialogDescription>
        </DialogHeader>

        <form id="grade-entry-form" class="flex-1 space-y-4 overflow-y-auto px-6 py-6" @submit.prevent="saveGrade">
          <div class="space-y-2">
            <Label for="grade-student">Student</Label>
            <Select v-model="form.student_id">
              <SelectTrigger id="grade-student" class="h-10 w-full">
                <SelectValue placeholder="Select student" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="s in students" :key="s.id" :value="String(s.id)">
                  {{ s.full_name ?? (s.student_number ? `Student ${s.student_number}` : 'Student') }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="grade-subject">Subject</Label>
            <Select v-model="form.subject_id">
              <SelectTrigger id="grade-subject" class="h-10 w-full">
                <SelectValue placeholder="Select subject" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="s in subjects" :key="s.id" :value="String(s.id)">
                  {{ s.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="grade-score">Score</Label>
              <Input id="grade-score" v-model="form.score" type="number" min="0" step="0.01" class="h-10 text-sm" required />
            </div>
            <div class="space-y-2">
              <Label for="grade-total">Out of</Label>
              <Input id="grade-total" v-model="form.total" type="number" min="1" step="0.01" class="h-10 text-sm" required />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="grade-term">Term</Label>
            <Select v-model="form.term">
              <SelectTrigger id="grade-term" class="h-10 w-full">
                <SelectValue placeholder="Select term" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="t in terms" :key="t.id" :value="String(t.id)">
                  {{ t.name }}
                  <span v-if="t.is_current" class="ml-1 text-xs text-muted-foreground">(Current)</span>
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="grade-type">Assessment type</Label>
            <Select v-model="form.assessment_type">
              <SelectTrigger id="grade-type" class="h-10 w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="test">Test</SelectItem>
                <SelectItem value="assignment">Assignment</SelectItem>
                <SelectItem value="exam">Exam</SelectItem>
                <SelectItem value="project">Project</SelectItem>
                <SelectItem value="quiz">Quiz</SelectItem>
                <SelectItem value="other">Other</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </form>

        <DialogFooter class="shrink-0 gap-2 border-t border-border/60 px-6 py-4 sm:flex-row sm:justify-end">
          <Button type="button" variant="outline" @click="modalOpen = false">Cancel</Button>
          <Button type="submit" form="grade-entry-form" :disabled="saving">
            {{ saving ? 'Saving…' : 'Save mark' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>
