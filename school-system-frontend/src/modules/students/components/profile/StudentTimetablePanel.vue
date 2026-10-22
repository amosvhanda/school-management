<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { BookOpen, Clock3, MapPin, UserRound } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { academicsApi } from '@/services/api.service'
import { STATUS_TONE_BADGE } from '@/lib/ui-status'
import { cn } from '@/lib/utils'

type TimetableSlot = Record<string, unknown> & {
  subject?: { name?: string } | string | null
  teacher?: { name?: string } | null
  subject_name?: string
  teacher_name?: string
}

const props = defineProps<{
  classId?: number | string | null
  classLabel?: string | null
}>()

const loading = ref(false)
const slots = ref<TimetableSlot[]>([])

function subjectLabel(slot: TimetableSlot) {
  if (slot.subject && typeof slot.subject === 'object') return slot.subject.name ?? 'Period'
  return String(slot.subject_name ?? slot.subject ?? 'Period')
}

function teacherLabel(slot: TimetableSlot) {
  return slot.teacher?.name ?? slot.teacher_name ?? 'Teacher TBC'
}

const todayName = computed(() =>
  new Date().toLocaleDateString(undefined, { weekday: 'long' }),
)

const todaySlots = computed(() => {
  const day = todayName.value.toLowerCase()
  return slots.value.filter((s) => {
    const d = String(s.day ?? s.day_of_week ?? s.weekday ?? '').toLowerCase()
    return d === day || d.startsWith(day.slice(0, 3))
  })
})

const weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as const

async function load() {
  if (!props.classId) {
    slots.value = []
    return
  }
  loading.value = true
  try {
    const rows = (await academicsApi.timetable.list({
      filter: { class_id: props.classId },
      per_page: 100,
    } as never)) as TimetableSlot[]
    slots.value = Array.isArray(rows) ? rows : []
  } catch {
    slots.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.classId, load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 border-b border-border/50 px-5 py-4">
        <CardTitle class="flex items-center gap-2 text-base">
          <Clock3 class="size-4 text-primary" aria-hidden="true" />
          Today's classes
        </CardTitle>
        <Badge variant="secondary" class="font-normal">
          {{ todayName }}
          <span v-if="todaySlots.length"> · {{ todaySlots.length }} periods</span>
        </Badge>
      </CardHeader>
      <CardContent class="space-y-3 p-5">
        <PageLoader v-if="loading" label="Loading timetable" />
        <template v-else-if="todaySlots.length">
          <article
            v-for="(slot, i) in todaySlots"
            :key="String(slot.id ?? i)"
            class="flex gap-3 rounded-2xl border border-border/70 p-3"
          >
            <div class="w-1 shrink-0 rounded-full bg-primary" aria-hidden="true" />
            <div class="min-w-0 flex-1 space-y-1">
              <p class="text-xs font-medium text-muted-foreground">
                {{ slot.start_time ?? slot.start ?? '—' }}
                –
                {{ slot.end_time ?? slot.end ?? '—' }}
              </p>
              <p class="text-sm font-semibold">
                {{ subjectLabel(slot) }}
              </p>
              <p class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                <span class="inline-flex items-center gap-1">
                  <UserRound class="size-3.5" aria-hidden="true" />
                  {{ teacherLabel(slot) }}
                </span>
                <span class="inline-flex items-center gap-1">
                  <MapPin class="size-3.5" aria-hidden="true" />
                  {{ slot.room ?? slot.venue ?? '—' }}
                </span>
              </p>
            </div>
          </article>
        </template>
        <EmptyState
          v-else
          variant="embedded"
          :title="classId ? 'No classes scheduled today' : 'No class assigned'"
          :description="classId ? 'Timetable slots for today will appear here.' : 'Assign a class to show the weekly timetable.'"
        />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="flex items-center gap-2 text-base">
          <BookOpen class="size-4" aria-hidden="true" />
          Weekly timetable
          <span v-if="classLabel" class="text-sm font-normal text-muted-foreground">· {{ classLabel }}</span>
        </CardTitle>
      </CardHeader>
      <CardContent class="overflow-x-auto p-5">
        <div v-if="slots.length" class="min-w-[40rem] space-y-2">
          <div
            v-for="day in weekDays"
            :key="day"
            class="grid grid-cols-[5rem_minmax(0,1fr)] items-start gap-3"
          >
            <p class="pt-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {{ day.slice(0, 3) }}
            </p>
            <div class="flex flex-wrap gap-2">
              <template
                v-for="(slot, i) in slots.filter((s) => {
                  const d = String(s.day ?? s.day_of_week ?? '').toLowerCase()
                  return d === day.toLowerCase() || d.startsWith(day.slice(0, 3).toLowerCase())
                })"
                :key="`${day}-${i}`"
              >
                <span
                  :class="cn(
                    'rounded-xl border px-2.5 py-1.5 text-xs font-medium',
                    STATUS_TONE_BADGE.info,
                  )"
                >
                  {{ subjectLabel(slot) }}
                  <span class="text-muted-foreground">
                    · {{ slot.start_time ?? slot.start ?? '' }}
                  </span>
                </span>
              </template>
              <span
                v-if="!slots.some((s) => {
                  const d = String(s.day ?? s.day_of_week ?? '').toLowerCase()
                  return d === day.toLowerCase() || d.startsWith(day.slice(0, 3).toLowerCase())
                })"
                class="text-xs text-muted-foreground"
              >
                —
              </span>
            </div>
          </div>
        </div>
        <EmptyState
          v-else-if="!loading"
          variant="embedded"
          title="Weekly grid unavailable"
          description="When a class timetable exists, periods will render in this grid."
        />
      </CardContent>
    </Card>
  </div>
</template>
