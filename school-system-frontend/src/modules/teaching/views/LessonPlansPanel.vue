<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Plus } from '@lucide/vue'
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
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

interface LessonPlan {
  id: number
  title: string
  plan_type?: string
  topic?: string
  status?: string
}

const loading = ref(true)
const error = ref<string | null>(null)
const plans = ref<LessonPlan[]>([])
const saving = ref(false)
const createOpen = ref(false)
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

const sortedPlans = computed(() => [...plans.value])

async function load() {
  loading.value = true
  error.value = null
  try {
    plans.value = (await teacherPortalApi.lessonPlans()) as LessonPlan[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load lesson plans')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  form.value = {
    title: '',
    plan_type: 'lesson',
    topic: '',
    objectives: '',
    outcomes: '',
    activities: '',
    resources: '',
    copy_from_id: '',
  }
  createOpen.value = true
}

async function save() {
  if (!form.value.title.trim()) {
    toast.warning('Add a title for this plan')
    return
  }
  saving.value = true
  try {
    const payload: Record<string, unknown> = { ...form.value }
    if (form.value.copy_from_id) payload.copy_from_id = Number(form.value.copy_from_id)
    else delete payload.copy_from_id
    await teacherPortalApi.createLessonPlan(payload)
    toast.success('Lesson plan saved')
    createOpen.value = false
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
    toast.success(status === 'completed' ? 'Marked complete' : 'Submitted for review')
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

onMounted(load)
</script>

<template>
  <TeachingPanelShell
    title="Lessons"
    description="Your plans — create one when you need it, then submit or complete."
    :loading="loading"
    loading-label="Loading lesson plans…"
    :error="error"
    :empty="!plans.length"
    empty-title="No lesson plans yet"
    empty-description="Create a plan for today’s lesson, a week, or a full topic."
    @retry="load"
  >
    <template #actions>
      <Button type="button" size="sm" @click="openCreate">
        <Plus class="size-4" aria-hidden="true" />
        New plan
      </Button>
    </template>

    <template #emptyAction>
      <Button type="button" @click="openCreate">
        <Plus class="size-4" aria-hidden="true" />
        Create first plan
      </Button>
    </template>

    <ul class="divide-y divide-border/60 overflow-hidden rounded-xl border border-border/60" role="list">
      <li
        v-for="p in sortedPlans"
        :key="p.id"
        class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="min-w-0">
          <p class="truncate font-medium text-foreground">{{ p.title }}</p>
          <p class="mt-0.5 text-sm text-muted-foreground">
            <span class="capitalize">{{ p.plan_type || 'lesson' }}</span>
            · {{ p.topic || 'No topic' }}
            ·
            <Badge variant="outline" class="capitalize">{{ p.status || 'draft' }}</Badge>
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="p.status !== 'submitted' && p.status !== 'completed'"
            size="sm"
            variant="outline"
            type="button"
            @click="setStatus(p.id, 'submitted')"
          >
            Submit
          </Button>
          <Button
            v-if="p.status !== 'completed'"
            size="sm"
            type="button"
            @click="setStatus(p.id, 'completed')"
          >
            Complete
          </Button>
        </div>
      </li>
    </ul>

    <Dialog v-model:open="createOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>New lesson plan</DialogTitle>
          <DialogDescription>
            Add objectives and activities. You can copy from an existing plan if needed.
          </DialogDescription>
        </DialogHeader>
        <form class="grid gap-4" @submit.prevent="save">
          <div class="space-y-2">
            <Label for="lp-title">Title</Label>
            <Input id="lp-title" v-model="form.title" required autocomplete="off" />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="lp-type">Plan type</Label>
              <Select v-model="form.plan_type">
                <SelectTrigger id="lp-type">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="lesson">Lesson</SelectItem>
                  <SelectItem value="weekly">Weekly</SelectItem>
                  <SelectItem value="term">Term</SelectItem>
                  <SelectItem value="topic">Topic</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="lp-topic">Topic</Label>
              <Input id="lp-topic" v-model="form.topic" />
            </div>
          </div>
          <div class="space-y-2">
            <Label for="lp-obj">Teaching objectives</Label>
            <Textarea id="lp-obj" v-model="form.objectives" rows="2" />
          </div>
          <div class="space-y-2">
            <Label for="lp-out">Learning outcomes</Label>
            <Textarea id="lp-out" v-model="form.outcomes" rows="2" />
          </div>
          <div class="space-y-2">
            <Label for="lp-act">Teaching activities</Label>
            <Textarea id="lp-act" v-model="form.activities" rows="2" />
          </div>
          <div class="space-y-2">
            <Label for="lp-res">Required resources</Label>
            <Textarea id="lp-res" v-model="form.resources" rows="2" />
          </div>
          <div class="space-y-2">
            <Label for="lp-copy">Copy from plan</Label>
            <Select
              :model-value="form.copy_from_id || 'none'"
              @update:model-value="(v) => (form.copy_from_id = v === 'none' ? '' : String(v ?? ''))"
            >
              <SelectTrigger id="lp-copy">
                <SelectValue placeholder="Optional" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None</SelectItem>
                <SelectItem
                  v-for="p in plans"
                  :key="p.id"
                  :value="String(p.id)"
                >
                  {{ p.title }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
            <Button type="submit" :disabled="saving" :aria-busy="saving">
              {{ saving ? 'Saving…' : 'Save plan' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </TeachingPanelShell>
</template>
