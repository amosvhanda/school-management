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
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const rows = ref<any[]>([])
const form = ref({ student_id: '', score: '3', notes: '', recorded_on: '' })

async function load() {
  loading.value = true
  error.value = null
  try {
    rows.value = (await teacherPortalApi.participation()) as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load participation')
  } finally {
    loading.value = false
  }
}

async function save() {
  try {
    await teacherPortalApi.recordParticipation({
      student_id: Number(form.value.student_id),
      score: Number(form.value.score),
      notes: form.value.notes || null,
      recorded_on: form.value.recorded_on || null,
    })
    toast.success('Participation recorded')
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
      <h2 class="text-lg font-semibold">Class participation</h2>
      <p class="text-sm text-muted-foreground">Record continuous assessment participation scores (1–5) alongside tests, assignments, and projects in Gradebook/Exams.</p>
    </div>
    <PageLoader v-if="loading" label="Loading…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Record participation</CardTitle>
          <CardDescription>Use Gradebook for marks entry and Exams for formal assessments.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
            <div class="space-y-2"><Label>Student ID</Label><Input v-model="form.student_id" type="number" required /></div>
            <div class="space-y-2"><Label>Score (1–5)</Label><Input v-model="form.score" type="number" min="1" max="5" required /></div>
            <div class="space-y-2"><Label>Date</Label><Input v-model="form.recorded_on" type="date" /></div>
            <div class="space-y-2 sm:col-span-2"><Label>Notes</Label><Textarea v-model="form.notes" rows="2" /></div>
            <div class="sm:col-span-2 flex justify-end"><Button type="submit">Save</Button></div>
          </form>
        </CardContent>
      </Card>
      <Card v-for="r in rows" :key="r.id" class="border-border/70">
        <CardContent class="py-3 text-sm">
          {{ r.student?.full_name || `Student #${r.student_id}` }} · score {{ r.score }} · {{ formatDate(r.recorded_on) }}
          <p v-if="r.notes" class="text-muted-foreground">{{ r.notes }}</p>
        </CardContent>
      </Card>
      <p v-if="!rows.length" class="text-sm text-muted-foreground">No participation records yet.</p>
    </template>
  </div>
</template>
