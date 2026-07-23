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
import { academicsApi, teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const assignments = ref<any[]>([])
const selectedId = ref<number | null>(null)
const submissions = ref<any[]>([])
const gradeForm = ref({ score: '', comment: '', status: 'graded' })

async function load() {
  loading.value = true
  error.value = null
  try {
    assignments.value = (await academicsApi.assignments.list({ limit: 50 })) as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load assignments')
  } finally {
    loading.value = false
  }
}

async function openSubs(id: number) {
  selectedId.value = id
  try {
    submissions.value = (await teacherPortalApi.submissions(id)) as any[]
  } catch (err) {
    toast.error(getErrorMessage(err))
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
  if (!selectedId.value) return
  const studentId = prompt('Student ID for submission')
  if (!studentId) return
  try {
    await teacherPortalApi.storeSubmission({
      assignment_id: selectedId.value,
      student_id: Number(studentId),
      status: 'submitted',
      content: 'Recorded by teacher',
    })
    toast.success('Submission recorded')
    await openSubs(selectedId.value)
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Homework &amp; submissions</h2>
      <p class="text-sm text-muted-foreground">View submissions, grade work, add comments, return, or request resubmission. Create homework from Assignments in Academics.</p>
    </div>
    <PageLoader v-if="loading" label="Loading homework…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <p v-if="!assignments.length" class="py-6 text-center text-sm text-muted-foreground">No assignments found. Create one under Academics → Assignments.</p>
      <div class="grid gap-4 lg:grid-cols-2">
        <Card v-for="a in assignments" :key="a.id" class="border-border/70">
          <CardHeader class="pb-2">
            <CardTitle class="text-base">{{ a.title || a.name }}</CardTitle>
            <CardDescription>Due {{ a.due_date || '—' }} · {{ a.total_marks ?? '—' }} marks</CardDescription>
          </CardHeader>
          <CardContent>
            <Button size="sm" @click="openSubs(a.id)">View submissions</Button>
          </CardContent>
        </Card>
      </div>

      <Card v-if="selectedId" class="border-border/70">
        <CardHeader class="flex flex-row items-center justify-between">
          <div>
            <CardTitle class="text-base">Submissions</CardTitle>
            <CardDescription>Grade, return, or request resubmission.</CardDescription>
          </div>
          <Button size="sm" variant="outline" @click="addSubmission">Record submission</Button>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-3 sm:grid-cols-3">
            <div class="space-y-2">
              <Label>Score</Label>
              <Input v-model="gradeForm.score" type="number" />
            </div>
            <div class="space-y-2">
              <Label>Action</Label>
              <select v-model="gradeForm.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                <option value="graded">Grade</option>
                <option value="returned">Return</option>
                <option value="resubmit">Request resubmission</option>
              </select>
            </div>
            <div class="space-y-2 sm:col-span-3">
              <Label>Comment</Label>
              <Textarea v-model="gradeForm.comment" rows="2" />
            </div>
          </div>
          <p v-if="!submissions.length" class="text-sm text-muted-foreground">No submissions yet.</p>
          <div v-for="s in submissions" :key="s.id" class="flex flex-col gap-2 rounded-lg border border-border/60 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="font-medium">{{ s.student?.full_name || `Student #${s.student_id}` }}</p>
              <p class="text-sm text-muted-foreground"><Badge variant="outline" class="capitalize">{{ s.status }}</Badge> · {{ s.score ?? '—' }}</p>
            </div>
            <Button size="sm" @click="grade(s.id)">Apply action</Button>
          </div>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
