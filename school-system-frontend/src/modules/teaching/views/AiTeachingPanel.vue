<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

const task = ref('lesson_plan')
const subject = ref('')
const topic = ref('')
const grade = ref('')
const studentName = ref('')
const result = ref<any | null>(null)
const busy = ref(false)

const tasks = [
  { value: 'lesson_plan', label: 'Generate lesson plan' },
  { value: 'quiz', label: 'Generate quiz' },
  { value: 'exam_paper', label: 'Generate exam paper' },
  { value: 'marking_guide', label: 'Generate marking guide' },
  { value: 'teaching_activities', label: 'Suggest teaching activities' },
  { value: 'class_analysis', label: 'Analyze class performance' },
  { value: 'interventions', label: 'Recommend interventions' },
  { value: 'student_comment', label: 'Generate student comment' },
  { value: 'progress_summary', label: 'Summarize learner progress' },
]

async function generate() {
  busy.value = true
  result.value = null
  try {
    result.value = await teacherPortalApi.aiGenerate({
      task: task.value,
      context: {
        subject: subject.value,
        topic: topic.value,
        grade: grade.value,
        student_name: studentName.value || undefined,
      },
    })
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">AI teacher assistant</h2>
      <p class="text-sm text-muted-foreground">Template-based generator (stub). Swap in a live LLM provider later without changing this UI.</p>
    </div>
    <Card class="border-border/70">
      <CardHeader>
        <CardTitle class="text-base">Generate teaching content</CardTitle>
        <CardDescription>Lesson plans, quizzes, papers, comments, and interventions.</CardDescription>
      </CardHeader>
      <CardContent>
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="generate">
          <div class="space-y-2 sm:col-span-2">
            <Label>Task</Label>
            <select v-model="task" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
              <option v-for="t in tasks" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
          </div>
          <div class="space-y-2"><Label>Subject</Label><Input v-model="subject" /></div>
          <div class="space-y-2"><Label>Topic</Label><Input v-model="topic" /></div>
          <div class="space-y-2"><Label>Class / grade</Label><Input v-model="grade" /></div>
          <div class="space-y-2"><Label>Student name (for comments)</Label><Input v-model="studentName" /></div>
          <div class="sm:col-span-2 flex justify-end">
            <Button type="submit" :disabled="busy" :aria-busy="busy">{{ busy ? 'Generating…' : 'Generate' }}</Button>
          </div>
        </form>
      </CardContent>
    </Card>
    <Card v-if="result" class="border-border/70">
      <CardHeader>
        <CardTitle class="text-base">{{ result.title }}</CardTitle>
        <CardDescription v-if="result.meta?.note">{{ result.meta.note }}</CardDescription>
      </CardHeader>
      <CardContent>
        <pre class="whitespace-pre-wrap rounded-lg bg-muted/40 p-4 text-sm">{{ result.content }}</pre>
      </CardContent>
    </Card>
  </div>
</template>
