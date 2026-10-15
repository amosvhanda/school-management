<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Clock, GraduationCap } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { formatDate, formatTime } from '@/lib/format'

const props = defineProps<{
  name?: string
  employeeId?: string | null
  department?: string | null
  subject?: string | null
  title?: string | null
  schoolName?: string | null
  todayLessons?: number
  teachingHours?: string
  upcomingClasses?: number
  pendingRegisters?: number
  profileHref?: string
}>()

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 17) return 'Good afternoon'
  return 'Good evening'
})

const firstName = computed(() => props.name?.trim() || 'Teacher')

const todayLine = computed(() => {
  const now = new Date()
  const weekday = now.toLocaleDateString('en-GB', {
    weekday: 'long',
    timeZone: 'Africa/Harare',
  })
  return `${weekday}, ${formatDate(now)}`
})

const chips = computed(() =>
  [props.employeeId, props.department, props.subject, props.title].filter(
    (value): value is string => Boolean(value && String(value).trim()),
  ),
)

const stats = computed(() => [
  { label: "Today's Schedule", value: String(props.todayLessons ?? 0) },
  { label: 'Teaching Hours', value: props.teachingHours || '—' },
  { label: 'Upcoming Classes', value: String(props.upcomingClasses ?? 0) },
  {
    label: 'Registers pending',
    value: String(props.pendingRegisters ?? 0),
  },
])
</script>

<template>
  <section
    class="overflow-hidden rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80 sm:p-5"
    aria-labelledby="teacher-greeting"
  >
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] xl:items-center">
      <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start">
        <div
          class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground"
          aria-hidden="true"
        >
          <GraduationCap class="size-8" />
        </div>
        <div class="min-w-0 space-y-3">
          <div>
            <p class="text-sm text-muted-foreground">{{ greeting }} 👋</p>
            <h2 id="teacher-greeting" class="mt-0.5 text-xl font-semibold tracking-tight sm:text-2xl">
              {{ firstName }}
            </h2>
            <p v-if="schoolName" class="mt-1 text-sm text-muted-foreground">{{ schoolName }}</p>
          </div>
          <ul v-if="chips.length" class="flex flex-wrap gap-1.5" aria-label="Teacher details">
            <li
              v-for="chip in chips"
              :key="chip"
              class="inline-flex h-7 items-center rounded-full bg-muted/70 px-2.5 text-xs font-medium text-foreground"
            >
              {{ chip }}
            </li>
          </ul>
          <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
            <Clock class="size-3.5" aria-hidden="true" />
            <span>{{ formatTime(new Date()) }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ todayLine }}</span>
          </p>
          <Button v-if="profileHref" size="sm" class="h-9 rounded-full px-4" as-child>
            <RouterLink :to="profileHref">View Profile</RouterLink>
          </Button>
        </div>
      </div>

      <ul class="grid gap-3 sm:grid-cols-2" aria-label="Today at a glance">
        <li
          v-for="stat in stats"
          :key="stat.label"
          class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60"
        >
          <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
          <p class="mt-2 text-2xl font-semibold tabular-nums tracking-tight">{{ stat.value }}</p>
        </li>
      </ul>
    </div>
  </section>
</template>
