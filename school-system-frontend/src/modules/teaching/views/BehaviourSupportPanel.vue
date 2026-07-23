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
const points = ref<any[]>([])
const interventions = ref<any[]>([])
const pointForm = ref({ student_id: '', points: '1', category: 'positive', description: '' })
const intForm = ref({ student_id: '', intervention_type: 'academic', summary: '', action_plan: '' })

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

async function savePoint() {
  try {
    await teacherPortalApi.recordBehaviour({
      student_id: Number(pointForm.value.student_id),
      points: Number(pointForm.value.points),
      category: pointForm.value.category,
      description: pointForm.value.description,
    })
    toast.success('Behaviour recorded')
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

async function saveIntervention() {
  try {
    await teacherPortalApi.createIntervention({
      student_id: Number(intForm.value.student_id),
      intervention_type: intForm.value.intervention_type,
      summary: intForm.value.summary,
      action_plan: intForm.value.action_plan || null,
    })
    toast.success('Support plan created')
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
      <h2 class="text-lg font-semibold">Behaviour &amp; student support</h2>
      <p class="text-sm text-muted-foreground">Record positive behaviour, rewards, misconduct, and create intervention plans.</p>
    </div>
    <PageLoader v-if="loading" label="Loading…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <div class="grid gap-6 lg:grid-cols-2">
        <Card class="border-border/70">
          <CardHeader>
            <CardTitle class="text-base">Record behaviour / reward</CardTitle>
            <CardDescription>Positive points or misconduct deductions.</CardDescription>
          </CardHeader>
          <CardContent>
            <form class="space-y-3" @submit.prevent="savePoint">
              <div class="space-y-2"><Label>Student ID</Label><Input v-model="pointForm.student_id" type="number" required /></div>
              <div class="space-y-2"><Label>Points (+/-)</Label><Input v-model="pointForm.points" type="number" required /></div>
              <div class="space-y-2">
                <Label>Category</Label>
                <select v-model="pointForm.category" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                  <option value="positive">Positive</option>
                  <option value="reward">Reward</option>
                  <option value="warning">Warning</option>
                  <option value="misconduct">Misconduct</option>
                  <option value="achievement">Achievement</option>
                </select>
              </div>
              <div class="space-y-2"><Label>Description</Label><Textarea v-model="pointForm.description" rows="2" required /></div>
              <Button type="submit">Save</Button>
            </form>
          </CardContent>
        </Card>
        <Card class="border-border/70">
          <CardHeader>
            <CardTitle class="text-base">Intervention / support plan</CardTitle>
            <CardDescription>For weak performance or attendance problems.</CardDescription>
          </CardHeader>
          <CardContent>
            <form class="space-y-3" @submit.prevent="saveIntervention">
              <div class="space-y-2"><Label>Student ID</Label><Input v-model="intForm.student_id" type="number" required /></div>
              <div class="space-y-2">
                <Label>Type</Label>
                <select v-model="intForm.intervention_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                  <option value="academic">Academic</option>
                  <option value="attendance">Attendance</option>
                  <option value="behaviour">Behaviour</option>
                  <option value="extra_lessons">Extra lessons</option>
                </select>
              </div>
              <div class="space-y-2"><Label>Summary</Label><Textarea v-model="intForm.summary" rows="2" required /></div>
              <div class="space-y-2"><Label>Action plan</Label><Textarea v-model="intForm.action_plan" rows="2" /></div>
              <Button type="submit">Create plan</Button>
            </form>
          </CardContent>
        </Card>
      </div>
      <section class="grid gap-4 lg:grid-cols-2">
        <div class="space-y-2">
          <h3 class="text-sm font-medium text-muted-foreground">Recent behaviour</h3>
          <Card v-for="p in points" :key="p.id" class="border-border/70">
            <CardContent class="py-3 text-sm">
              {{ p.student?.full_name }} · {{ p.points }} pts · <Badge variant="outline">{{ p.category }}</Badge>
              <p class="text-muted-foreground">{{ p.description }}</p>
            </CardContent>
          </Card>
          <p v-if="!points.length" class="text-sm text-muted-foreground">No behaviour records.</p>
        </div>
        <div class="space-y-2">
          <h3 class="text-sm font-medium text-muted-foreground">Support plans</h3>
          <Card v-for="i in interventions" :key="i.id" class="border-border/70">
            <CardContent class="py-3 text-sm">
              {{ i.student?.full_name }} · <Badge variant="outline">{{ i.intervention_type }}</Badge>
              <p class="text-muted-foreground">{{ i.summary }}</p>
            </CardContent>
          </Card>
          <p v-if="!interventions.length" class="text-sm text-muted-foreground">No interventions yet.</p>
        </div>
      </section>
    </template>
  </div>
</template>
