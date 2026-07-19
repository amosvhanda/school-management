<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { CalendarDays, RefreshCw } from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage, unwrapList } from '@/lib/api-response'
import { formatTime } from '@/lib/format'
import { api } from '@/lib/api'
import { endpoints } from '@/services/endpoints'

interface TimetableSlot {
  id: number
  day?: string
  start_time?: string
  end_time?: string
  subject?: string | { name?: string }
  room?: string | null
  class_model?: { id?: number; name?: string } | null
  teacher?: { id?: number; name?: string; first_name?: string; last_name?: string } | null
  class_id?: number
  teacher_id?: number
}

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as const

const { user } = useAuth()
const loading = ref(true)
const error = ref<string | null>(null)
const slots = ref<TimetableSlot[]>([])
const scope = ref<string | null>(null)

const isTeacher = computed(() => user.value?.role === 'teacher')
const isStudent = computed(() => user.value?.role === 'student')

const pageTitle = computed(() => (isTeacher.value ? 'My teaching timetable' : 'My class timetable'))
const pageDescription = computed(() => {
  if (isTeacher.value) {
    return 'Lessons where you are the assigned subject teacher.'
  }
  const className = user.value?.class_name
  return className
    ? `Weekly schedule for ${className}.`
    : 'Weekly schedule for your class.'
})

function subjectLabel(slot: TimetableSlot): string {
  if (typeof slot.subject === 'string' && slot.subject.trim()) return slot.subject
  if (slot.subject && typeof slot.subject === 'object' && slot.subject.name) return slot.subject.name
  return 'Lesson'
}

function teacherLabel(slot: TimetableSlot): string {
  const t = slot.teacher
  if (!t) return '—'
  if (t.name?.trim()) return t.name
  const parts = [t.first_name, t.last_name].filter(Boolean)
  return parts.length ? parts.join(' ') : '—'
}

function classLabel(slot: TimetableSlot): string {
  return slot.class_model?.name?.trim() || '—'
}

function clock(value?: string): string {
  return formatTime(value, '—')
}

const periodKeys = computed(() => {
  const keys = new Set<string>()
  for (const slot of slots.value) {
    const start = clock(slot.start_time)
    const end = clock(slot.end_time)
    if (start !== '—' && end !== '—') keys.add(`${start}|${end}`)
  }
  return [...keys].sort((a, b) => a.localeCompare(b))
})

const grid = computed(() => {
  const map = new Map<string, TimetableSlot>()
  for (const slot of slots.value) {
    const day = slot.day || ''
    const start = clock(slot.start_time)
    const end = clock(slot.end_time)
    if (!day || start === '—' || end === '—') continue
    map.set(`${day}|${start}|${end}`, slot)
  }
  return map
})

function cell(day: string, periodKey: string): TimetableSlot | null {
  const [start, end] = periodKey.split('|')
  return grid.value.get(`${day}|${start}|${end}`) ?? null
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get(endpoints.timetable.list)
    slots.value = unwrapList<TimetableSlot>(data)
    const meta = (data as { meta?: { scope?: string } })?.meta
    scope.value = meta?.scope ?? null
  } catch (e) {
    error.value = getErrorMessage(e, 'Could not load your timetable.')
    slots.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell :title="pageTitle" :description="pageDescription">
    <template #actions>
      <Button type="button" variant="outline" size="sm" :disabled="loading" @click="load">
        <RefreshCw class="mr-2 size-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
        Refresh
      </Button>
    </template>

    <Alert v-if="error" variant="destructive" class="mb-4" role="alert">
      <AlertDescription>{{ error }}</AlertDescription>
    </Alert>

    <PageLoader v-if="loading" class="py-16" label="Loading timetable…" />

    <template v-else-if="!slots.length">
      <EmptyState
        title="No timetable yet"
        :description="isStudent
          ? 'Your class timetable has not been published. Ask the school office if subjects are assigned.'
          : 'No lessons are assigned to you yet. Ask admin to generate the timetable from teacher assignments.'"
      >
        <CalendarDays class="size-8 text-muted-foreground" aria-hidden="true" />
      </EmptyState>
    </template>

    <template v-else>
      <div class="mb-4 flex flex-wrap items-center gap-2">
        <Badge variant="secondary" class="text-xs font-medium">
          {{ slots.length }} lesson{{ slots.length === 1 ? '' : 's' }}
        </Badge>
        <Badge v-if="isStudent && user?.class_name" variant="outline" class="text-xs">
          {{ user.class_name }}
        </Badge>
        <Badge v-if="scope" variant="outline" class="text-xs capitalize">
          {{ scope }} view
        </Badge>
      </div>

      <!-- Desktop / tablet weekly grid -->
      <div class="hidden overflow-x-auto rounded-xl border border-border/60 md:block" role="region" aria-label="Weekly timetable">
        <table class="w-full min-w-[48rem] border-collapse text-sm">
          <thead>
            <tr class="border-b border-border/60 bg-muted/40">
              <th scope="col" class="sticky left-0 z-10 bg-muted/40 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                Period
              </th>
              <th
                v-for="day in DAYS"
                :key="day"
                scope="col"
                class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground"
              >
                {{ day }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="periodKey in periodKeys"
              :key="periodKey"
              class="border-b border-border/40 last:border-0"
            >
              <th
                scope="row"
                class="sticky left-0 z-10 whitespace-nowrap bg-background px-3 py-3 text-left text-xs font-medium text-muted-foreground"
              >
                {{ periodKey.replace('|', '–') }}
              </th>
              <td
                v-for="day in DAYS"
                :key="`${day}-${periodKey}`"
                class="align-top px-2 py-2"
              >
                <div
                  v-if="cell(day, periodKey)"
                  class="rounded-lg border border-border/50 bg-card px-3 py-2 shadow-sm"
                >
                  <p class="font-medium text-foreground">
                    {{ subjectLabel(cell(day, periodKey)!) }}
                  </p>
                  <p v-if="isTeacher" class="mt-1 text-xs text-muted-foreground">
                    {{ classLabel(cell(day, periodKey)!) }}
                  </p>
                  <p v-else class="mt-1 text-xs text-muted-foreground">
                    {{ teacherLabel(cell(day, periodKey)!) }}
                  </p>
                  <p
                    v-if="cell(day, periodKey)?.room"
                    class="mt-0.5 text-xs text-muted-foreground/80"
                  >
                    {{ cell(day, periodKey)!.room }}
                  </p>
                </div>
                <span v-else class="block min-h-[3.5rem] rounded-lg bg-muted/20" aria-hidden="true" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile day cards -->
      <div class="space-y-4 md:hidden" role="list" aria-label="Timetable by day">
        <section
          v-for="day in DAYS"
          :key="day"
          class="rounded-xl border border-border/60 p-4"
          role="listitem"
        >
          <h2 class="mb-3 text-sm font-semibold tracking-tight">{{ day }}</h2>
          <ul class="space-y-2">
            <li
              v-for="slot in slots.filter((s) => s.day === day)"
              :key="slot.id"
              class="rounded-lg border border-border/40 px-3 py-2"
            >
              <p class="text-xs text-muted-foreground">
                {{ clock(slot.start_time) }}–{{ clock(slot.end_time) }}
              </p>
              <p class="font-medium text-foreground">{{ subjectLabel(slot) }}</p>
              <p class="text-xs text-muted-foreground">
                <template v-if="isTeacher">{{ classLabel(slot) }}</template>
                <template v-else>{{ teacherLabel(slot) }}</template>
                <template v-if="slot.room"> · {{ slot.room }}</template>
              </p>
            </li>
            <li
              v-if="!slots.some((s) => s.day === day)"
              class="text-sm text-muted-foreground"
            >
              No lessons
            </li>
          </ul>
        </section>
      </div>
    </template>
  </PageShell>
</template>
