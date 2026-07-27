<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { teachersApi, teacherPortalApi } from '@/services/api.service'

interface OnlineLesson {
  id: number
  title: string
  lesson_type?: string
  status?: string
  scheduled_at?: string | null
  meeting_url?: string | null
  recording_url?: string | null
  description?: string | null
  teacher?: { id: number; name?: string; email?: string | null } | null
}

interface TeacherOption {
  id: number
  name: string
}

const { checkCapability, user } = useAuth()
const isSchoolManager = computed(() => checkCapability('canManageTeachers'))

const loading = ref(true)
const error = ref<string | null>(null)
const lessons = ref<OnlineLesson[]>([])
const teachers = ref<TeacherOption[]>([])
const saving = ref(false)
const form = ref({
  title: '',
  lesson_type: 'live',
  teacher_id: '',
  scheduled_at: '',
  description: '',
  recording_url: '',
})

async function loadTeachers() {
  if (!isSchoolManager.value) return
  try {
    const rows = await teachersApi.list({ all: true, status: 'active' }) as TeacherOption[]
    teachers.value = Array.isArray(rows)
      ? rows.map((row) => ({ id: Number(row.id), name: String(row.name ?? `Teacher #${row.id}`) }))
      : []
  } catch {
    teachers.value = []
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    await loadTeachers()
    const rows = await teacherPortalApi.onlineLessons()
    lessons.value = Array.isArray(rows) ? rows as OnlineLesson[] : []
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load online lessons')
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!form.value.title.trim()) {
    toast.warning('Title required')
    return
  }
  if (isSchoolManager.value && !user.value?.teacher_id && !form.value.teacher_id) {
    toast.warning('Select a teacher for this lesson')
    return
  }

  saving.value = true
  try {
    await teacherPortalApi.createOnlineLesson({
      title: form.value.title,
      lesson_type: form.value.lesson_type,
      scheduled_at: form.value.scheduled_at || null,
      description: form.value.description || null,
      recording_url: form.value.recording_url || null,
      ...(form.value.teacher_id ? { teacher_id: Number(form.value.teacher_id) } : {}),
    })
    toast.success('Online lesson created')
    form.value = {
      title: '',
      lesson_type: 'live',
      teacher_id: form.value.teacher_id,
      scheduled_at: '',
      description: '',
      recording_url: '',
    }
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Online learning / LMS</h2>
      <p class="text-sm text-muted-foreground">
        <template v-if="isSchoolManager">
          Manage school-wide virtual lessons, recordings, quizzes, and polls. Assign each session to a teacher.
        </template>
        <template v-else>
          Schedule virtual lessons, share recordings, and create quizzes or polls. Live meeting links are stubbed until a video provider is connected.
        </template>
      </p>
    </div>

    <PageLoader v-if="loading" label="Loading LMS…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Create online class</CardTitle>
          <CardDescription v-if="isSchoolManager">
            School admins can create LMS sessions for any teacher.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
            <div class="space-y-2 sm:col-span-2">
              <Label for="ol-title">Title</Label>
              <Input id="ol-title" v-model="form.title" required />
            </div>

            <div v-if="isSchoolManager" class="space-y-2 sm:col-span-2">
              <Label for="ol-teacher">Teacher</Label>
              <Select v-model="form.teacher_id">
                <SelectTrigger id="ol-teacher" aria-required="true">
                  <SelectValue placeholder="Select a teacher…" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="teacher in teachers"
                    :key="teacher.id"
                    :value="String(teacher.id)"
                  >
                    {{ teacher.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="!teachers.length" class="text-xs text-muted-foreground">
                Add teachers under People → Teachers before creating LMS sessions.
              </p>
            </div>

            <div class="space-y-2">
              <Label for="ol-type">Type</Label>
              <select
                id="ol-type"
                v-model="form.lesson_type"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              >
                <option value="live">Live class</option>
                <option value="recorded">Recorded lesson</option>
                <option value="discussion">Discussion forum</option>
                <option value="quiz">Online quiz</option>
                <option value="poll">Poll</option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="ol-when">Scheduled at</Label>
              <Input id="ol-when" v-model="form.scheduled_at" type="datetime-local" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="ol-rec">Recording / video URL</Label>
              <Input id="ol-rec" v-model="form.recording_url" placeholder="https://…" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="ol-desc">Description</Label>
              <Textarea id="ol-desc" v-model="form.description" rows="2" />
            </div>
            <div class="sm:col-span-2 flex justify-end">
              <Button type="submit" :disabled="saving || (isSchoolManager && !teachers.length)">
                {{ saving ? 'Saving…' : 'Create' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <p v-if="!lessons.length" class="py-6 text-center text-sm text-muted-foreground">
        No online lessons yet.
      </p>

      <Card v-for="lesson in lessons" :key="lesson.id" class="border-border/70">
        <CardContent class="space-y-2 py-4">
          <div class="flex flex-wrap items-center gap-2">
            <p class="font-medium">{{ lesson.title }}</p>
            <Badge variant="outline" class="capitalize">{{ lesson.lesson_type }}</Badge>
            <Badge variant="secondary" class="capitalize">{{ lesson.status }}</Badge>
          </div>
          <p v-if="isSchoolManager && lesson.teacher?.name" class="text-sm text-muted-foreground">
            Teacher: {{ lesson.teacher.name }}
          </p>
          <p class="text-sm text-muted-foreground">{{ lesson.scheduled_at || 'Unscheduled' }}</p>
          <div class="flex flex-wrap gap-3">
            <a
              v-if="lesson.meeting_url"
              :href="lesson.meeting_url"
              class="text-sm text-primary underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none"
              target="_blank"
              rel="noopener"
            >
              Open meeting (stub)
            </a>
            <a
              v-if="lesson.recording_url"
              :href="lesson.recording_url"
              class="text-sm text-primary underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none"
              target="_blank"
              rel="noopener"
            >
              Recording
            </a>
          </div>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
