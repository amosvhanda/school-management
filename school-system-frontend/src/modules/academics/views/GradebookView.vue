<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Plus, Search, SlidersHorizontal } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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
import { getErrorMessage } from '@/lib/api-response'
import { academicsApi } from '@/services/api.service'
import { fetchList } from '@/services/dashboard.service'
import { moduleEndpoints } from '@/services'

// Auth session import to safely manage teacher scopes
import { useAuthStore } from '@/stores/auth.store'

interface ClassOption { id: number; name: string }
interface SubjectOption { id: number; name: string }
interface StudentOption { id: number; full_name?: string; student_number?: string }
interface TermOption { id: number; name: string; is_current?: boolean }
interface GradeRow {
  id: number
  subject?: string
  score?: number
  total?: number
  grade?: string
  term?: string
  assessment_type?: string
  student?: { full_name?: string }
}

const { toast } = useToast()
const authStore = useAuthStore()

const classes = ref<ClassOption[]>([])
const subjects = ref<SubjectOption[]>([])
const students = ref<StudentOption[]>([])
const terms = ref<TermOption[]>([])
const selectedClass = ref<string>('')
const grades = ref<GradeRow[]>([])
const loading = ref(true)
const gradesLoading = ref(false)
const error = ref<string | null>(null)
const modalOpen = ref(false)
const saving = ref(false)

// Inline Table Filters & Search State
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

// Check if the current logged-in user is a teacher role
const isTeacher = computed(() => authStore.user?.role === 'teacher')
const teacherId = computed(() => authStore.user?.teacher_id)

// Computed reactive filter pipeline for real-time sorting
const filteredGrades = computed(() => {
  return grades.value.filter((g) => {
    const matchesSearch = !searchQuery.value ||
      g.student?.full_name?.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesType = selectedTypeFilter.value === 'all' ||
      g.assessment_type === selectedTypeFilter.value

    return matchesSearch && matchesType
  })
})

async function loadClasses() {
  loading.value = true
  error.value = null

  // Set up filters: If they are a teacher, pin requests to their teacher_id
  const filterParams = isTeacher.value && teacherId.value
    ? { teacher_id: teacherId.value }
    : { all: true }

  try {
    const [classRows, subjectRows, termRows] = await Promise.all([
      // Scopes the classes dropdown to just the classes this teacher handles
      fetchList<ClassOption>(moduleEndpoints.classes, filterParams),
      // Scopes the subjects list to just what this teacher runs
      fetchList<SubjectOption>(moduleEndpoints.subjects, filterParams),
      // Terms are universal for the calendar track
      fetchList<TermOption>(moduleEndpoints.terms, { all: true }),
    ])

    classes.value = classRows
    subjects.value = subjectRows
    terms.value = termRows

    if (classes.value.length && !selectedClass.value) {
      selectedClass.value = String(classes.value[0].id)
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load gradebook layers'
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

const avgScore = computed(() => {
  const valid = grades.value.filter((g) => g.total && g.total > 0)
  if (!valid.length) return null
  const sum = valid.reduce((acc, g) => acc + ((g.score ?? 0) / (g.total ?? 1)) * 100, 0)
  return (sum / valid.length).toFixed(1)
})

function openEntry() {
  const activeTermId = terms.value.find(t => t.is_current)?.id || terms.value[0]?.id || ''

  form.value = {
    student_id: '',
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
      description: 'Please select a student and subject before continuing.',
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
    toast({ title: 'Grade recorded successfully' })
    modalOpen.value = false
    await loadGrades()
  } catch (err) {
    toast({
      title: 'Could not save grade',
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
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-foreground">Gradebook</h1>
        <p class="text-sm text-muted-foreground">Enter and review marks by class</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <div class="space-y-1">
          <Label for="class-select" class="sr-only">Class Selector</Label>
          <Select v-model="selectedClass">
            <SelectTrigger id="class-select" class="w-48 h-10">
              <SelectValue placeholder="Select class" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="cls in classes" :key="cls.id" :value="String(cls.id)">
                {{ cls.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <Button class="h-10 px-4" @click="openEntry">
          <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
          Enter marks
        </Button>
      </div>
    </div>

    <PageLoader v-if="loading" label="Loading gradebook layers…" />
    <ErrorState v-else-if="error" :description="error" @retry="loadClasses" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Records</CardTitle>
          </CardHeader>
          <CardContent class="text-2xl font-bold text-foreground">{{ grades.length }}</CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Class average</CardTitle>
          </CardHeader>
          <CardContent class="text-2xl font-bold text-foreground">
            {{ avgScore != null ? `${avgScore}%` : '—' }}
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Students in class</CardTitle>
          </CardHeader>
          <CardContent class="text-2xl font-bold text-foreground">{{ students.length }}</CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="pb-4">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <CardTitle class="text-base font-semibold tracking-tight">Grade entries</CardTitle>

            <!-- Table Sub-Filter & Search Input Row -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center w-full sm:w-auto">
              <div class="relative w-full sm:w-64">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground/70" aria-hidden="true" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search student name..."
                  class="pl-9 h-9 text-sm"
                />
              </div>

              <div class="flex items-center gap-2 w-full sm:w-auto">
                <SlidersHorizontal class="h-4 w-4 text-muted-foreground shrink-0 hidden sm:block" aria-hidden="true" />
                <Select v-model="selectedTypeFilter">
                  <SelectTrigger class="w-full sm:w-40 h-9 text-xs">
                    <SelectValue placeholder="All Assessment Types" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Assessments</SelectItem>
                    <SelectItem value="test">Tests</SelectItem>
                    <SelectItem value="assignment">Assignments</SelectItem>
                    <SelectItem value="exam">Exams</SelectItem>
                    <SelectItem value="project">Projects</SelectItem>
                    <SelectItem value="quiz">Quizzes</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>
        </CardHeader>

        <CardContent class="p-0">
          <PageLoader v-if="gradesLoading" class="py-12" label="Syncing entry metrics…" />
          <div v-else-if="filteredGrades.length" class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="text-xs font-medium">Student</TableHead>
                  <TableHead class="text-xs font-medium">Subject</TableHead>
                  <TableHead class="text-xs font-medium">Term</TableHead>
                  <TableHead class="text-xs font-medium">Score</TableHead>
                  <TableHead class="text-xs font-medium">Grade</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="g in filteredGrades" :key="g.id" class="transition-colors">
                  <TableCell class="font-medium text-sm text-foreground">{{ g.student?.full_name ?? '—' }}</TableCell>
                  <TableCell class="text-sm text-foreground/90">
                    <div class="flex flex-col gap-0.5">
                      <span>{{ g.subject ?? '—' }}</span>
                      <span class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium sm:hidden">
                        {{ g.assessment_type }}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell class="text-sm text-muted-foreground">{{ g.term ?? '—' }}</TableCell>
                  <TableCell class="text-sm font-mono tracking-tight text-foreground">
                    {{ g.score ?? '—' }}{{ g.total ? ` / ${g.total}` : '' }}
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <Badge variant="outline" class="font-normal text-xs px-2 py-0.5">{{ g.grade ?? '—' }}</Badge>
                      <Badge variant="secondary" class="font-medium text-[10px] uppercase tracking-wider px-1.5 py-0 hidden sm:inline-flex">
                        {{ g.assessment_type ?? 'test' }}
                      </Badge>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <p v-else class="py-12 text-center text-sm text-muted-foreground italic">
            {{ grades.length ? 'No logs match your search filters.' : 'No grades for this class yet. Use "Enter marks" to add the first record.' }}
          </p>
        </CardContent>
      </Card>
    </template>

    <Dialog v-model:open="modalOpen">
      <DialogContent class="flex max-h-[85vh] w-full max-w-md flex-col gap-0 overflow-hidden p-0 rounded-xl shadow-lg border">
        <DialogHeader class="shrink-0 space-y-1 border-b border-muted/60 px-6 pb-4 pt-6">
          <DialogTitle class="text-base font-semibold tracking-tight">Enter grade</DialogTitle>
          <DialogDescription class="text-xs leading-relaxed">
            Record a mark for a student in the selected class.
          </DialogDescription>
        </DialogHeader>

        <form id="grade-entry-form" class="flex-1 overflow-y-auto px-6 py-6 space-y-4" @submit.prevent="saveGrade">
          <div class="space-y-2">
            <Label for="grade-student">Student</Label>
            <Select v-model="form.student_id">
              <SelectTrigger id="grade-student" class="w-full h-10">
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
              <SelectTrigger id="grade-subject" class="w-full h-10">
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
              <SelectTrigger id="grade-term" class="w-full h-10">
                <SelectValue placeholder="Select active term" />
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
              <SelectTrigger id="grade-type" class="w-full h-10">
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

        <DialogFooter class="shrink-0 border-t border-muted/60 px-6 py-4 sm:flex-row sm:justify-end gap-2">
          <Button type="button" variant="outline" @click="modalOpen = false">Cancel</Button>
          <Button type="submit" form="grade-entry-form" :disabled="saving">
            {{ saving ? 'Saving…' : 'Save grade' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
