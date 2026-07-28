<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { studentPortalApi } from '@/services/api.service'
import { getErrorMessage } from '@/lib/api-response'
import { toast } from 'vue-sonner'

type CbtSubject = {
  subject_id: number
  subject_name: string
  question_count: number
}

type CbtQuestion = {
  id: number
  question_text: string
  question_type?: string | null
  options?: string[] | Record<string, string> | null
  marks?: number | null
}

type CbtSession = {
  id: number
  status: string
  score?: number | string | null
  questions: CbtQuestion[]
}

const subjects = ref<CbtSubject[]>([])
const activeSessionMeta = ref<{ id: number } | null>(null)
const session = ref<CbtSession | null>(null)
const loading = ref(true)
const busy = ref(false)
const error = ref<string | null>(null)
const answers = reactive<Record<number, string>>({})
const selectedSubjectId = ref<number | null>(null)

function optionEntries(q: CbtQuestion): Array<{ key: string; label: string }> {
  const opts = q.options
  if (!opts) return []
  if (Array.isArray(opts)) {
    return opts.map((label) => ({ key: String(label), label: String(label) }))
  }
  return Object.entries(opts).map(([key, label]) => ({ key, label: String(label) }))
}

async function loadAvailable() {
  loading.value = true
  error.value = null
  try {
    const data = (await studentPortalApi.cbtAvailable()) as {
      subjects?: CbtSubject[]
      active_session?: { id: number } | null
    }
    subjects.value = data.subjects ?? []
    activeSessionMeta.value = data.active_session ?? null
    if (data.active_session?.id) {
      await openSession(data.active_session.id)
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Unable to load online exams.')
  } finally {
    loading.value = false
  }
}

async function startExam() {
  if (!selectedSubjectId.value) {
    toast.error('Select a subject first')
    return
  }
  busy.value = true
  try {
    const started = (await studentPortalApi.cbtStart({
      subject_id: selectedSubjectId.value,
      count: 10,
    })) as CbtSession
    session.value = started
    Object.keys(answers).forEach((k) => delete answers[Number(k)])
    toast.success('Exam started')
  } catch (err) {
    toast.error(getErrorMessage(err, 'Could not start exam'))
  } finally {
    busy.value = false
  }
}

async function openSession(id: number) {
  busy.value = true
  try {
    session.value = (await studentPortalApi.cbtSession(id)) as CbtSession
  } catch (err) {
    toast.error(getErrorMessage(err, 'Could not open session'))
  } finally {
    busy.value = false
  }
}

async function submitExam() {
  if (!session.value) return
  const payload = Object.entries(answers).map(([question_id, answer]) => ({
    question_id: Number(question_id),
    answer,
  }))
  if (!payload.length) {
    toast.error('Answer at least one question before submitting')
    return
  }
  busy.value = true
  try {
    const result = (await studentPortalApi.cbtSubmit(session.value.id, payload)) as {
      score?: number | string
      status?: string
    }
    toast.success(`Submitted — score ${result.score ?? 'n/a'}%`)
    session.value = null
    await loadAvailable()
  } catch (err) {
    toast.error(getErrorMessage(err, 'Submit failed'))
  } finally {
    busy.value = false
  }
}

function onVisibility() {
  if (!session.value || session.value.status !== 'in_progress') return
  if (document.visibilityState === 'hidden') {
    void studentPortalApi.cbtAntiCheat(session.value.id, 'tab_blur').catch(() => undefined)
  }
}

onMounted(() => {
  void loadAvailable()
  document.addEventListener('visibilitychange', onVisibility)
})

onUnmounted(() => {
  document.removeEventListener('visibilitychange', onVisibility)
})

watch(
  () => subjects.value,
  (list) => {
    if (!selectedSubjectId.value && list[0]) {
      selectedSubjectId.value = list[0].subject_id
    }
  },
)
</script>

<template>
  <Card class="border-border/70">
    <CardHeader>
      <CardTitle class="text-base">Online exams (CBT)</CardTitle>
      <CardDescription>
        Take available computer-based tests for your subjects. Leaving the tab is logged.
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <p v-if="loading" class="text-sm text-muted-foreground" role="status">Loading online exams…</p>
      <p v-else-if="error" class="text-sm text-destructive" role="alert">{{ error }}</p>

      <template v-else-if="!session">
        <p v-if="!subjects.length" class="text-sm text-muted-foreground">
          No question banks are available for online exams yet.
        </p>
        <div v-else class="space-y-3">
          <div class="space-y-2">
            <Label for="cbt-subject">Subject</Label>
            <select
              id="cbt-subject"
              v-model.number="selectedSubjectId"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
              <option v-for="s in subjects" :key="s.subject_id" :value="s.subject_id">
                {{ s.subject_name }} ({{ s.question_count }} questions)
              </option>
            </select>
          </div>
          <Button type="button" :disabled="busy" @click="startExam">
            {{ busy ? 'Starting…' : 'Start practice exam' }}
          </Button>
        </div>
      </template>

      <template v-else>
        <div class="space-y-6">
          <div
            v-for="(q, index) in session.questions"
            :key="q.id"
            class="space-y-2 rounded-xl border border-border/60 p-4"
          >
            <p class="text-sm font-medium text-foreground">
              {{ index + 1 }}. {{ q.question_text }}
              <span v-if="q.marks" class="text-muted-foreground"> ({{ q.marks }} marks)</span>
            </p>
            <div v-if="optionEntries(q).length" class="space-y-2">
              <label
                v-for="opt in optionEntries(q)"
                :key="opt.key"
                class="flex items-center gap-2 text-sm"
              >
                <input
                  v-model="answers[q.id]"
                  type="radio"
                  class="size-4"
                  :name="`q-${q.id}`"
                  :value="opt.key"
                />
                {{ opt.label }}
              </label>
            </div>
            <Input
              v-else
              v-model="answers[q.id]"
              :placeholder="'Your answer'"
              autocomplete="off"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <Button type="button" :disabled="busy" @click="submitExam">
              {{ busy ? 'Submitting…' : 'Submit exam' }}
            </Button>
            <Button type="button" variant="outline" :disabled="busy" @click="session = null">
              Close
            </Button>
          </div>
        </div>
      </template>
    </CardContent>
  </Card>
</template>
