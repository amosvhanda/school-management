<script setup lang="ts">
import { onMounted, ref } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<any>({ events: [], assignment_deadlines: [], exams: [] })

async function load() {
  loading.value = true
  error.value = null
  try {
    data.value = await teacherPortalApi.calendar()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load calendar')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Teacher calendar</h2>
      <p class="text-sm text-muted-foreground">School events, exams, holidays context, and assignment deadlines.</p>
    </div>
    <PageLoader v-if="loading" label="Loading calendar…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <div v-else class="grid gap-4 lg:grid-cols-3">
      <Card class="border-border/70">
        <CardHeader><CardTitle class="text-base">School events</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <p v-if="!(data.events || []).length" class="text-sm text-muted-foreground">No events.</p>
          <div v-for="e in data.events || []" :key="e.id" class="rounded-lg border border-border/60 px-3 py-2 text-sm">
            <p class="font-medium">{{ e.title || e.name }}</p>
            <p class="text-muted-foreground">{{ formatDate(e.start_date || e.date) }}</p>
          </div>
        </CardContent>
      </Card>
      <Card class="border-border/70">
        <CardHeader><CardTitle class="text-base">Exams</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <p v-if="!(data.exams || []).length" class="text-sm text-muted-foreground">No upcoming exams.</p>
          <div v-for="e in data.exams || []" :key="e.id" class="rounded-lg border border-border/60 px-3 py-2 text-sm">
            <p class="font-medium">{{ e.name }}</p>
            <p class="text-muted-foreground">{{ formatDate(e.exam_date) }} · <Badge variant="outline">{{ e.status }}</Badge></p>
          </div>
        </CardContent>
      </Card>
      <Card class="border-border/70">
        <CardHeader><CardTitle class="text-base">Assignment deadlines</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <p v-if="!(data.assignment_deadlines || []).length" class="text-sm text-muted-foreground">No deadlines.</p>
          <div v-for="a in data.assignment_deadlines || []" :key="a.id" class="rounded-lg border border-border/60 px-3 py-2 text-sm">
            <p class="font-medium">{{ a.title }}</p>
            <p class="text-muted-foreground">{{ formatDate(a.due_date) }}</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
