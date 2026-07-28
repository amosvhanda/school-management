<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { teacherPortalApi } from '@/services/api.service'

type CalendarEvent = {
  id: number
  title?: string
  name?: string
  starts_at?: string | null
  start_date?: string | null
  date?: string | null
  type?: string | null
  location?: string | null
}

type AcademicEntry = {
  id: number
  title: string
  entry_type?: string
  start_date?: string | null
  end_date?: string | null
  is_holiday?: boolean
}

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<{
  events: CalendarEvent[]
  academic_entries: AcademicEntry[]
  assignment_deadlines: Array<{ id: number; title: string; due_date?: string | null }>
  exams: Array<{ id: number; name: string; exam_date?: string | null; status?: string }>
}>({ events: [], academic_entries: [], assignment_deadlines: [], exams: [] })

const upcoming = computed(() => {
  const items: Array<{ key: string; title: string; date: string | null; kind: string }> = []

  for (const e of data.value.events || []) {
    items.push({
      key: `event-${e.id}`,
      title: e.title || e.name || 'School event',
      date: e.starts_at || e.start_date || e.date || null,
      kind: e.type || 'Event',
    })
  }
  for (const a of data.value.academic_entries || []) {
    items.push({
      key: `academic-${a.id}`,
      title: a.title,
      date: a.start_date || null,
      kind: a.is_holiday ? 'Holiday' : a.entry_type || 'Academic',
    })
  }
  for (const exam of data.value.exams || []) {
    items.push({
      key: `exam-${exam.id}`,
      title: exam.name,
      date: exam.exam_date || null,
      kind: 'Exam',
    })
  }
  for (const assignment of data.value.assignment_deadlines || []) {
    items.push({
      key: `assignment-${assignment.id}`,
      title: assignment.title,
      date: assignment.due_date || null,
      kind: 'Deadline',
    })
  }

  return items
    .filter((item) => item.date)
    .sort((a, b) => String(a.date).localeCompare(String(b.date)))
    .slice(0, 40)
})

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
      <p class="text-sm text-muted-foreground">
        School events, academic holidays, exams, and assignment deadlines in one place.
      </p>
    </div>
    <PageLoader v-if="loading" label="Loading calendar…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Upcoming</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <EmptyState
            v-if="!upcoming.length"
            title="Nothing scheduled"
            description="School events, holidays, exams, and deadlines will appear here."
          />
          <div
            v-for="item in upcoming"
            :key="item.key"
            class="flex items-start justify-between gap-3 rounded-lg border border-border/60 px-3 py-2 text-sm"
          >
            <div class="min-w-0">
              <p class="truncate font-medium">{{ item.title }}</p>
              <p class="text-muted-foreground">{{ formatDate(item.date) }}</p>
            </div>
            <Badge variant="outline">{{ item.kind }}</Badge>
          </div>
        </CardContent>
      </Card>

      <div class="grid gap-4 lg:grid-cols-3">
        <Card class="border-border/70">
          <CardHeader><CardTitle class="text-base">School events</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <p v-if="!(data.events || []).length" class="text-sm text-muted-foreground">No events.</p>
            <div
              v-for="e in data.events || []"
              :key="e.id"
              class="rounded-lg border border-border/60 px-3 py-2 text-sm"
            >
              <p class="font-medium">{{ e.title || e.name }}</p>
              <p class="text-muted-foreground">{{ formatDate(e.starts_at || e.start_date || e.date) }}</p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-border/70">
          <CardHeader><CardTitle class="text-base">Academic / holidays</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <p v-if="!(data.academic_entries || []).length" class="text-sm text-muted-foreground">No academic entries.</p>
            <div
              v-for="a in data.academic_entries || []"
              :key="a.id"
              class="rounded-lg border border-border/60 px-3 py-2 text-sm"
            >
              <p class="font-medium">{{ a.title }}</p>
              <p class="text-muted-foreground">
                {{ formatDate(a.start_date) }}
                <Badge v-if="a.is_holiday" variant="outline" class="ml-2">Holiday</Badge>
              </p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-border/70">
          <CardHeader><CardTitle class="text-base">Exams & deadlines</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <p
              v-if="!(data.exams || []).length && !(data.assignment_deadlines || []).length"
              class="text-sm text-muted-foreground"
            >
              No exams or deadlines.
            </p>
            <div
              v-for="e in data.exams || []"
              :key="`exam-${e.id}`"
              class="rounded-lg border border-border/60 px-3 py-2 text-sm"
            >
              <p class="font-medium">{{ e.name }}</p>
              <p class="text-muted-foreground">
                {{ formatDate(e.exam_date) }} · <Badge variant="outline">{{ e.status || 'Exam' }}</Badge>
              </p>
            </div>
            <div
              v-for="a in data.assignment_deadlines || []"
              :key="`assignment-${a.id}`"
              class="rounded-lg border border-border/60 px-3 py-2 text-sm"
            >
              <p class="font-medium">{{ a.title }}</p>
              <p class="text-muted-foreground">{{ formatDate(a.due_date) }}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </template>
  </div>
</template>
