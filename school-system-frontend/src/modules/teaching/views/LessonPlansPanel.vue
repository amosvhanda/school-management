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
const plans = ref<any[]>([])
const saving = ref(false)
const form = ref({
  title: '',
  plan_type: 'lesson',
  topic: '',
  objectives: '',
  outcomes: '',
  activities: '',
  resources: '',
  copy_from_id: '',
})

async function load() {
  loading.value = true
  error.value = null
  try {
    plans.value = (await teacherPortalApi.lessonPlans()) as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load lesson plans')
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
    const payload: Record<string, unknown> = { ...form.value }
    if (form.value.copy_from_id) payload.copy_from_id = Number(form.value.copy_from_id)
    else delete payload.copy_from_id
    await teacherPortalApi.createLessonPlan(payload)
    toast.success('Lesson plan saved')
    form.value = { title: '', plan_type: 'lesson', topic: '', objectives: '', outcomes: '', activities: '', resources: '', copy_from_id: '' }
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function setStatus(id: number, status: string) {
  try {
    await teacherPortalApi.updateLessonPlan(id, { status })
    toast.success(`Marked ${status}`)
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
      <h2 class="text-lg font-semibold text-foreground">Lesson planning</h2>
      <p class="text-sm text-muted-foreground">Create weekly/term/topic plans, copy previous lessons, and track completion.</p>
    </div>
    <PageLoader v-if="loading" label="Loading…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>

      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">New lesson plan</CardTitle>
          <CardDescription>Include objectives, outcomes, activities, and resources.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
            <div class="space-y-2 sm:col-span-2">
              <Label for="lp-title">Title</Label>
              <Input id="lp-title" v-model="form.title" required />
            </div>
            <div class="space-y-2">
              <Label for="lp-type">Plan type</Label>
              <select id="lp-type" v-model="form.plan_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                <option value="lesson">Lesson</option>
                <option value="weekly">Weekly</option>
                <option value="term">Term</option>
                <option value="topic">Topic</option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="lp-topic">Topic</Label>
              <Input id="lp-topic" v-model="form.topic" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="lp-obj">Teaching objectives</Label>
              <Textarea id="lp-obj" v-model="form.objectives" rows="2" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="lp-out">Learning outcomes</Label>
              <Textarea id="lp-out" v-model="form.outcomes" rows="2" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="lp-act">Teaching activities</Label>
              <Textarea id="lp-act" v-model="form.activities" rows="2" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="lp-res">Required resources</Label>
              <Textarea id="lp-res" v-model="form.resources" rows="2" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="lp-copy">Copy from existing plan (optional ID)</Label>
              <Input id="lp-copy" v-model="form.copy_from_id" placeholder="e.g. 12" />
            </div>
            <div class="sm:col-span-2 flex justify-end">
              <Button type="submit" :disabled="saving" :aria-busy="saving">{{ saving ? 'Saving…' : 'Save plan' }}</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <div class="space-y-3">
        <p v-if="!plans.length" class="py-6 text-center text-sm text-muted-foreground">No lesson plans yet.</p>
        <Card v-for="p in plans" :key="p.id" class="border-border/70">
          <CardContent class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="font-medium">{{ p.title }}</p>
              <p class="text-sm text-muted-foreground">{{ p.plan_type }} · {{ p.topic || '—' }} · <Badge variant="outline" class="capitalize">{{ p.status }}</Badge></p>
            </div>
            <div class="flex flex-wrap gap-2">
              <Button size="sm" variant="outline" @click="setStatus(p.id, 'submitted')">Submit</Button>
              <Button size="sm" @click="setStatus(p.id, 'completed')">Complete</Button>
            </div>
          </CardContent>
        </Card>
      </div>

    </template>
  </div>
</template>
