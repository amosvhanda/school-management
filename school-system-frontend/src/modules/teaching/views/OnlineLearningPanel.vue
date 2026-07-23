<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const lessons = ref<any[]>([])
const saving = ref(false)
const form = ref({ title: '', lesson_type: 'live', scheduled_at: '', description: '', recording_url: '' })

async function load() {
  loading.value = true
  error.value = null
  try {
    lessons.value = (await teacherPortalApi.onlineLessons()) as any[]
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
  saving.value = true
  try {
    await teacherPortalApi.createOnlineLesson({ ...form.value, scheduled_at: form.value.scheduled_at || null })
    toast.success('Online lesson created')
    form.value = { title: '', lesson_type: 'live', scheduled_at: '', description: '', recording_url: '' }
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
      <p class="text-sm text-muted-foreground">Schedule virtual lessons, share recordings, and create quizzes or polls. Live meeting links are stubbed until a video provider is connected.</p>
    </div>
    <PageLoader v-if="loading" label="Loading LMS…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Create online class</CardTitle>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
            <div class="space-y-2 sm:col-span-2">
              <Label for="ol-title">Title</Label>
              <Input id="ol-title" v-model="form.title" required />
            </div>
            <div class="space-y-2">
              <Label for="ol-type">Type</Label>
              <select id="ol-type" v-model="form.lesson_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
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
              <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Create' }}</Button>
            </div>
          </form>
        </CardContent>
      </Card>
      <p v-if="!lessons.length" class="py-6 text-center text-sm text-muted-foreground">No online lessons yet.</p>
      <Card v-for="l in lessons" :key="l.id" class="border-border/70">
        <CardContent class="space-y-2 py-4">
          <div class="flex flex-wrap items-center gap-2">
            <p class="font-medium">{{ l.title }}</p>
            <Badge variant="outline" class="capitalize">{{ l.lesson_type }}</Badge>
            <Badge variant="secondary" class="capitalize">{{ l.status }}</Badge>
          </div>
          <p class="text-sm text-muted-foreground">{{ l.scheduled_at || 'Unscheduled' }}</p>
          <a v-if="l.meeting_url" :href="l.meeting_url" class="text-sm text-primary underline-offset-4 hover:underline" target="_blank" rel="noopener">Open meeting (stub)</a>
          <a v-if="l.recording_url" :href="l.recording_url" class="ml-3 text-sm text-primary underline-offset-4 hover:underline" target="_blank" rel="noopener">Recording</a>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
