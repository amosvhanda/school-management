<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import TeachingPanelShell from '@/components/teaching/TeachingPanelShell.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { useTeacherClassLearners } from '@/composables/useTeacherClassLearners'
import { getErrorMessage } from '@/lib/api-response'
import { academicsApi, teacherPortalApi } from '@/services/api.service'

interface AssignmentRow {
  id: number
  title?: string
  name?: string
  due_date?: string
  total_marks?: number
}

interface SubmissionRow {
  id: number
  student_id?: number
  score?: number | null
  status?: string
  student?: { full_name?: string }
}

const loading = ref(true)
const error = ref<string | null>(null)
const assignments = ref<AssignmentRow[]>([])
const selectedId = ref<number | null>(null)
const submissions = ref<SubmissionRow[]>([])
const subsLoading = ref(false)
const gradeForm = ref({ score: '', comment: '', status: 'graded' })
const recordOpen = ref(false)
const recordStudentId = ref('')
const recording = ref(false)

const { allLearners } = useTeacherClassLearners()

const selectedAssignment = computed(
  () => assignments.value.find((a) => a.id === selectedId.value) ?? null,
)

async function load() {
  loading.value = true
  error.value = null
  try {
    assignments.value = (await academicsApi.assignments.list({ limit: 50 })) as AssignmentRow[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load homework')
  } finally {
    loading.value = false
  }
}

async function openSubs(id: number) {
  selectedId.value = id
  subsLoading.value = true
  try {
    submissions.value = (await teacherPortalApi.submissions(id)) as SubmissionRow[]
  } catch (err) {
    toast.error(getErrorMessage(err))
    submissions.value = []
  } finally {
    subsLoading.value = false
  }
}

async function grade(id: number) {
  try {
    await teacherPortalApi.gradeSubmission(id, {
      score: gradeForm.value.score ? Number(gradeForm.value.score) : null,
      teacher_comment: gradeForm.value.comment || null,
      status: gradeForm.value.status,
    })
    toast.success('Submission updated')
    if (selectedId.value) await openSubs(selectedId.value)
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

async function addSubmission() {
  if (!selectedId.value || !recordStudentId.value) {
    toast.warning('Choose a learner')
    return
  }
  recording.value = true
  try {
    await teacherPortalApi.storeSubmission({
      assignment_id: selectedId.value,
      student_id: Number(recordStudentId.value),
      status: 'submitted',
      content: 'Recorded by teacher',
    })
    toast.success('Submission recorded')
    recordOpen.value = false
    recordStudentId.value = ''
    await openSubs(selectedId.value)
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    recording.value = false
  }
}

onMounted(load)
</script>

<template>
  <TeachingPanelShell
    title="Homework"
    description="Open an assignment, then grade or return submissions."
    :loading="loading"
    loading-label="Loading homework…"
    :error="error"
    :empty="!assignments.length"
    empty-title="No homework yet"
    empty-description="Create assignments from Academics when you are ready to set work."
    @retry="load"
  >
    <div class="grid gap-6 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
      <section aria-label="Assignments" class="space-y-2">
        <p class="px-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
          Assignments
        </p>
        <ul class="space-y-1.5" role="listbox" aria-label="Homework assignments">
          <li v-for="a in assignments" :key="a.id">
            <button
              type="button"
              role="option"
              :aria-selected="selectedId === a.id"
              class="flex w-full flex-col rounded-xl border px-3 py-3 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              :class="selectedId === a.id
                ? 'border-primary/30 bg-primary/5 shadow-sm'
                : 'border-border/60 hover:bg-muted/40'"
              @click="openSubs(a.id)"
            >
              <span class="truncate text-sm font-semibold text-foreground">
                {{ a.title || a.name }}
              </span>
              <span class="mt-0.5 text-xs text-muted-foreground">
                Due {{ a.due_date || '—' }} · {{ a.total_marks ?? '—' }} marks
              </span>
            </button>
          </li>
        </ul>
      </section>

      <section
        v-if="selectedAssignment"
        class="space-y-4"
        :aria-label="`${selectedAssignment.title || selectedAssignment.name} submissions`"
      >
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border/50 pb-4">
          <div>
            <h3 class="font-heading text-base font-semibold text-foreground">
              {{ selectedAssignment.title || selectedAssignment.name }}
            </h3>
            <p class="text-sm text-muted-foreground">
              Due {{ selectedAssignment.due_date || '—' }}
            </p>
          </div>
          <Button type="button" size="sm" variant="outline" @click="recordOpen = true">
            Record submission
          </Button>
        </div>

        <div class="grid gap-3 rounded-xl border border-border/60 bg-muted/15 p-3 sm:grid-cols-3">
          <div class="space-y-2">
            <Label for="hw-score">Score</Label>
            <Input id="hw-score" v-model="gradeForm.score" type="number" />
          </div>
          <div class="space-y-2">
            <Label for="hw-action">Action</Label>
            <Select v-model="gradeForm.status">
              <SelectTrigger id="hw-action">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="graded">Grade</SelectItem>
                <SelectItem value="returned">Return</SelectItem>
                <SelectItem value="resubmit">Request resubmission</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2 sm:col-span-3">
            <Label for="hw-comment">Comment</Label>
            <Textarea id="hw-comment" v-model="gradeForm.comment" rows="2" />
          </div>
        </div>

        <p v-if="subsLoading" class="text-sm text-muted-foreground">Loading submissions…</p>
        <p
          v-else-if="!submissions.length"
          class="rounded-xl border border-dashed border-border/70 px-4 py-8 text-center text-sm text-muted-foreground"
          role="status"
        >
          No submissions yet.
        </p>
        <ul v-else class="divide-y divide-border/60 overflow-hidden rounded-xl border border-border/60">
          <li
            v-for="s in submissions"
            :key="s.id"
            class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <p class="font-medium text-foreground">
                {{ s.student?.full_name || `Student #${s.student_id}` }}
              </p>
              <p class="text-sm text-muted-foreground">
                <Badge variant="outline" class="capitalize">{{ s.status }}</Badge>
                · {{ s.score ?? '—' }}
              </p>
            </div>
            <Button size="sm" type="button" @click="grade(s.id)">Apply action</Button>
          </li>
        </ul>
      </section>

      <p
        v-else
        class="rounded-xl border border-dashed border-border/70 px-4 py-12 text-center text-sm text-muted-foreground lg:col-start-2"
        role="status"
      >
        Select an assignment to review submissions.
      </p>
    </div>

    <Dialog v-model:open="recordOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Record submission</DialogTitle>
          <DialogDescription>
            Use this when a learner handed work in offline.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-2">
          <Label for="hw-student">Learner</Label>
          <Select v-model="recordStudentId">
            <SelectTrigger id="hw-student">
              <SelectValue placeholder="Select learner" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="s in allLearners"
                :key="s.id"
                :value="String(s.id)"
              >
                {{ s.name }} · {{ s.className }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="recordOpen = false">Cancel</Button>
          <Button type="button" :disabled="recording" :aria-busy="recording" @click="addSubmission">
            {{ recording ? 'Saving…' : 'Record' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </TeachingPanelShell>
</template>
