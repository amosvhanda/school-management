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
const rows = ref<any[]>([])
const saving = ref(false)
const form = ref({
  student_id: '',
  academic_comment: '',
  behaviour_comment: '',
  recommendations: '',
  strengths: '',
  areas_for_improvement: '',
  status: 'draft',
})

async function load() {
  loading.value = true
  error.value = null
  try {
    rows.value = (await teacherPortalApi.reportCards()) as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load report cards')
  } finally {
    loading.value = false
  }
}

async function save(submit = false) {
  if (!form.value.student_id) {
    toast.warning('Student ID required')
    return
  }
  saving.value = true
  try {
    await teacherPortalApi.saveReportCard({
      ...form.value,
      student_id: Number(form.value.student_id),
      status: submit ? 'submitted' : form.value.status,
    })
    toast.success(submit ? 'Report card submitted' : 'Draft saved')
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
      <h2 class="text-lg font-semibold">Report card narratives</h2>
      <p class="text-sm text-muted-foreground">Write academic and behaviour comments, strengths, areas for improvement, and recommendations.</p>
    </div>
    <PageLoader v-if="loading" label="Loading report cards…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Write / update narrative</CardTitle>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4" @submit.prevent="save(false)">
            <div class="space-y-2">
              <Label for="rc-student">Student ID</Label>
              <Input id="rc-student" v-model="form.student_id" type="number" required />
            </div>
            <div class="space-y-2">
              <Label>Academic comment</Label>
              <Textarea v-model="form.academic_comment" rows="2" />
            </div>
            <div class="space-y-2">
              <Label>Behaviour comment</Label>
              <Textarea v-model="form.behaviour_comment" rows="2" />
            </div>
            <div class="space-y-2">
              <Label>Strengths</Label>
              <Textarea v-model="form.strengths" rows="2" />
            </div>
            <div class="space-y-2">
              <Label>Areas needing improvement</Label>
              <Textarea v-model="form.areas_for_improvement" rows="2" />
            </div>
            <div class="space-y-2">
              <Label>Recommendations</Label>
              <Textarea v-model="form.recommendations" rows="2" />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="submit" variant="outline" :disabled="saving">Save draft</Button>
              <Button type="button" :disabled="saving" @click="save(true)">Submit</Button>
            </div>
          </form>
        </CardContent>
      </Card>
      <p v-if="!rows.length" class="py-6 text-center text-sm text-muted-foreground">No narratives yet.</p>
      <Card v-for="r in rows" :key="r.id" class="border-border/70">
        <CardContent class="space-y-2 py-4">
          <div class="flex items-center gap-2">
            <p class="font-medium">{{ r.student?.full_name || `Student #${r.student_id}` }}</p>
            <Badge variant="outline" class="capitalize">{{ r.status }}</Badge>
          </div>
          <p class="text-sm text-muted-foreground">{{ r.academic_comment || 'No academic comment' }}</p>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
