<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const topics = ref<any[]>([])
const saving = ref(false)
const form = ref({ subject_id: '', title: '', chapter: '', objectives: '' })

async function load() {
  loading.value = true
  error.value = null
  try {
    topics.value = (await teacherPortalApi.syllabusTopics()) as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load syllabus')
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!form.value.subject_id || !form.value.title) {
    toast.warning('Subject ID and title are required')
    return
  }
  saving.value = true
  try {
    await teacherPortalApi.createSyllabusTopic({
      subject_id: Number(form.value.subject_id),
      title: form.value.title,
      chapter: form.value.chapter || null,
      objectives: form.value.objectives || null,
    })
    toast.success('Topic added')
    form.value = { subject_id: form.value.subject_id, title: '', chapter: '', objectives: '' }
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function markDone(id: number) {
  try {
    await teacherPortalApi.updateSyllabusTopic(id, { status: 'completed', coverage_percent: 100 })
    toast.success('Topic completed')
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Syllabus & topics</h2>
      <p class="text-sm text-muted-foreground">Manage chapters, learning objectives, and coverage.</p>
    </div>
    <PageLoader v-if="loading" label="Loading syllabus…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Add topic / chapter</CardTitle>
          <CardDescription>Track syllabus progress for an assigned subject.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
            <div class="space-y-2">
              <Label for="sub-id">Subject ID</Label>
              <Input id="sub-id" v-model="form.subject_id" required type="number" />
            </div>
            <div class="space-y-2">
              <Label for="chapter">Chapter</Label>
              <Input id="chapter" v-model="form.chapter" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="topic-title">Topic title</Label>
              <Input id="topic-title" v-model="form.title" required />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="topic-obj">Learning objectives</Label>
              <Textarea id="topic-obj" v-model="form.objectives" rows="2" />
            </div>
            <div class="sm:col-span-2 flex justify-end">
              <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Add topic' }}</Button>
            </div>
          </form>
        </CardContent>
      </Card>
      <p v-if="!topics.length" class="py-6 text-center text-sm text-muted-foreground">No syllabus topics yet.</p>
      <Card v-for="t in topics" :key="t.id" class="border-border/70">
        <CardContent class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="font-medium">{{ t.title }}</p>
            <p class="text-sm text-muted-foreground">
              {{ t.subject?.name || `Subject #${t.subject_id}` }}
              <span v-if="t.chapter"> · {{ t.chapter }}</span>
              · {{ t.coverage_percent }}% · <Badge variant="outline" class="capitalize">{{ t.status }}</Badge>
            </p>
          </div>
          <Button size="sm" variant="outline" @click="markDone(t.id)">Mark completed</Button>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
