<script setup lang="ts">
import { onMounted, ref } from 'vue'
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
import { useTeacherClassLearners } from '@/composables/useTeacherClassLearners'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const points = ref<any[]>([])
const interventions = ref<any[]>([])
const pointOpen = ref(false)
const planOpen = ref(false)
const saving = ref(false)

const pointForm = ref({ student_id: '', points: '1', category: 'positive', description: '' })
const intForm = ref({ student_id: '', intervention_type: 'academic', summary: '', action_plan: '' })

const { allLearners, loading: learnersLoading } = useTeacherClassLearners()

async function load() {
  loading.value = true
  error.value = null
  try {
    const [p, i] = await Promise.all([
      teacherPortalApi.behaviour(),
      teacherPortalApi.interventions(),
    ])
    points.value = p as any[]
    interventions.value = i as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load behaviour data')
  } finally {
    loading.value = false
  }
}

function openPoint() {
  pointForm.value = { student_id: '', points: '1', category: 'positive', description: '' }
  pointOpen.value = true
}

function openPlan() {
  intForm.value = { student_id: '', intervention_type: 'academic', summary: '', action_plan: '' }
  planOpen.value = true
}

async function savePoint() {
  if (!pointForm.value.student_id) {
    toast.warning('Choose a learner')
    return
  }
  saving.value = true
  try {
    await teacherPortalApi.recordBehaviour({
      student_id: Number(pointForm.value.student_id),
      points: Number(pointForm.value.points),
      category: pointForm.value.category,
      description: pointForm.value.description,
    })
    toast.success('Behaviour recorded')
    pointOpen.value = false
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function saveIntervention() {
  if (!intForm.value.student_id) {
    toast.warning('Choose a learner')
    return
  }
  saving.value = true
  try {
    await teacherPortalApi.createIntervention({
      student_id: Number(intForm.value.student_id),
      intervention_type: intForm.value.intervention_type,
      summary: intForm.value.summary,
      action_plan: intForm.value.action_plan || null,
    })
    toast.success('Support plan created')
    planOpen.value = false
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
  <TeachingPanelShell
    title="Behaviour"
    description="Recent notes and support plans for your learners."
    :loading="loading || learnersLoading"
    loading-label="Loading behaviour…"
    :error="error"
    :empty="!points.length && !interventions.length"
    empty-title="No behaviour records yet"
    empty-description="Record a positive note, warning, or open a support plan."
    @retry="load"
  >
    <template #actions>
      <Button type="button" size="sm" variant="outline" @click="openPlan">Support plan</Button>
      <Button type="button" size="sm" @click="openPoint">
        <Plus class="size-4" aria-hidden="true" />
        Record
      </Button>
    </template>

    <template #emptyAction>
      <Button type="button" @click="openPoint">
        <Plus class="size-4" aria-hidden="true" />
        Record behaviour
      </Button>
    </template>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="space-y-3" aria-label="Recent behaviour">
        <h3 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
          Recent behaviour
        </h3>
        <ul
          v-if="points.length"
          class="divide-y divide-border/60 overflow-hidden rounded-xl border border-border/60"
        >
          <li v-for="p in points" :key="p.id" class="px-4 py-3 text-sm">
            <p class="font-medium text-foreground">
              {{ p.student?.full_name || 'Learner' }}
              · {{ p.points }} pts
              ·
              <Badge variant="outline" class="capitalize">{{ p.category }}</Badge>
            </p>
            <p class="mt-1 text-muted-foreground">{{ p.description }}</p>
          </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">No behaviour notes yet.</p>
      </section>

      <section class="space-y-3" aria-label="Support plans">
        <h3 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
          Support plans
        </h3>
        <ul
          v-if="interventions.length"
          class="divide-y divide-border/60 overflow-hidden rounded-xl border border-border/60"
        >
          <li v-for="i in interventions" :key="i.id" class="px-4 py-3 text-sm">
            <p class="font-medium text-foreground">
              {{ i.student?.full_name || 'Learner' }}
              ·
              <Badge variant="outline" class="capitalize">{{ i.intervention_type }}</Badge>
            </p>
            <p class="mt-1 text-muted-foreground">{{ i.summary }}</p>
          </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">No support plans yet.</p>
      </section>
    </div>

    <Dialog v-model:open="pointOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Record behaviour</DialogTitle>
          <DialogDescription>Positive points, rewards, or misconduct notes.</DialogDescription>
        </DialogHeader>
        <form class="space-y-3" @submit.prevent="savePoint">
          <div class="space-y-2">
            <Label for="bh-student">Learner</Label>
            <Select v-model="pointForm.student_id">
              <SelectTrigger id="bh-student">
                <SelectValue placeholder="Select learner" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="s in allLearners" :key="s.id" :value="String(s.id)">
                  {{ s.name }} · {{ s.className }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="bh-points">Points (+/-)</Label>
              <Input id="bh-points" v-model="pointForm.points" type="number" required />
            </div>
            <div class="space-y-2">
              <Label for="bh-cat">Category</Label>
              <Select v-model="pointForm.category">
                <SelectTrigger id="bh-cat">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="positive">Positive</SelectItem>
                  <SelectItem value="reward">Reward</SelectItem>
                  <SelectItem value="warning">Warning</SelectItem>
                  <SelectItem value="misconduct">Misconduct</SelectItem>
                  <SelectItem value="achievement">Achievement</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div class="space-y-2">
            <Label for="bh-desc">Description</Label>
            <Textarea id="bh-desc" v-model="pointForm.description" rows="2" required />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="pointOpen = false">Cancel</Button>
            <Button type="submit" :disabled="saving" :aria-busy="saving">
              {{ saving ? 'Saving…' : 'Save' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="planOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Support plan</DialogTitle>
          <DialogDescription>For academic, attendance, or behaviour concerns.</DialogDescription>
        </DialogHeader>
        <form class="space-y-3" @submit.prevent="saveIntervention">
          <div class="space-y-2">
            <Label for="sp-student">Learner</Label>
            <Select v-model="intForm.student_id">
              <SelectTrigger id="sp-student">
                <SelectValue placeholder="Select learner" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="s in allLearners" :key="s.id" :value="String(s.id)">
                  {{ s.name }} · {{ s.className }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="sp-type">Type</Label>
            <Select v-model="intForm.intervention_type">
              <SelectTrigger id="sp-type">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="academic">Academic</SelectItem>
                <SelectItem value="attendance">Attendance</SelectItem>
                <SelectItem value="behaviour">Behaviour</SelectItem>
                <SelectItem value="extra_lessons">Extra lessons</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="sp-summary">Summary</Label>
            <Textarea id="sp-summary" v-model="intForm.summary" rows="2" required />
          </div>
          <div class="space-y-2">
            <Label for="sp-plan">Action plan</Label>
            <Textarea id="sp-plan" v-model="intForm.action_plan" rows="2" />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="planOpen = false">Cancel</Button>
            <Button type="submit" :disabled="saving" :aria-busy="saving">
              {{ saving ? 'Saving…' : 'Create plan' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </TeachingPanelShell>
</template>
