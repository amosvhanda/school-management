<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { CalendarDays } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import EmptyState from '@/components/feedback/EmptyState.vue'
import type { SchoolDashboardCalendarEvent } from '@/types/dashboard'

const props = defineProps<{ events: SchoolDashboardCalendarEvent[] }>()

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const monthLabel = computed(() =>
  new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(new Date()),
)

const todayIso = computed(() => new Date().toISOString().slice(0, 10))

const eventsByDay = computed(() => {
  const map = new Map<string, SchoolDashboardCalendarEvent[]>()
  for (const event of props.events) {
    if (!event.starts_at) continue
    const day = event.starts_at.slice(0, 10)
    const list = map.get(day) ?? []
    list.push(event)
    map.set(day, list)
  }
  return map
})

const cells = computed(() => {
  const now = new Date()
  const year = now.getFullYear()
  const month = now.getMonth()
  const first = new Date(year, month, 1)
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const startPad = first.getDay()

  const result: Array<{
    key: string
    day: number | null
    iso: string | null
    isToday: boolean
    count: number
    titles: string[]
  }> = []

  for (let i = 0; i < startPad; i += 1) {
    result.push({ key: `pad-${i}`, day: null, iso: null, isToday: false, count: 0, titles: [] })
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    const iso = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    const dayEvents = eventsByDay.value.get(iso) ?? []
    result.push({
      key: iso,
      day,
      iso,
      isToday: iso === todayIso.value,
      count: dayEvents.length,
      titles: dayEvents.map((event) => event.title),
    })
  }

  return result
})

const upcomingInMonth = computed(() =>
  [...props.events]
    .filter((event) => event.starts_at)
    .sort((a, b) => String(a.starts_at).localeCompare(String(b.starts_at)))
    .slice(0, 4),
)
</script>

<template>
  <Card class="h-full">
    <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pb-4 space-y-0">
      <div class="space-y-1">
        <CardTitle class="text-base font-semibold tracking-tight">Calendar</CardTitle>
        <CardDescription>{{ monthLabel }}</CardDescription>
      </div>
      <Button variant="ghost" size="sm" class="shrink-0" as-child>
        <RouterLink to="/operations/events" aria-label="View all events">
          <CalendarDays class="size-4" aria-hidden="true" />
        </RouterLink>
      </Button>
    </CardHeader>
    <CardContent class="space-y-4 px-5 pt-4">
      <div role="grid" :aria-label="`School calendar for ${monthLabel}`" class="space-y-2">
        <div role="row" class="grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-muted-foreground">
          <span v-for="day in weekdays" :key="day" role="columnheader">{{ day }}</span>
        </div>
        <div role="rowgroup" class="grid grid-cols-7 gap-1">
          <div
            v-for="cell in cells"
            :key="cell.key"
            role="gridcell"
            class="flex aspect-square flex-col items-center justify-center rounded-md text-xs"
            :class="[
              cell.day == null ? 'text-transparent' : 'text-foreground',
              cell.isToday ? 'bg-primary text-primary-foreground font-semibold' : '',
              cell.count && !cell.isToday ? 'bg-chart-2/15 font-medium text-chart-2' : '',
            ]"
            :title="cell.titles.length ? cell.titles.join(', ') : undefined"
            :aria-label="cell.day
              ? `${cell.iso}${cell.count ? `, ${cell.count} event${cell.count === 1 ? '' : 's'}` : ''}`
              : undefined"
          >
            <span v-if="cell.day != null">{{ cell.day }}</span>
            <span
              v-if="cell.count > 0"
              class="mt-0.5 size-1 rounded-full"
              :class="cell.isToday ? 'bg-primary-foreground' : 'bg-chart-2'"
              aria-hidden="true"
            />
          </div>
        </div>
      </div>

      <EmptyState
        v-if="!upcomingInMonth.length"
        class="py-4"
        title="No events this month"
        description="Scheduled school events will highlight on the calendar."
      />
      <ul v-else class="space-y-2" aria-label="Events this month">
        <li v-for="event in upcomingInMonth" :key="event.id" class="text-sm">
          <p class="font-medium leading-snug">{{ event.title }}</p>
          <p class="text-xs text-muted-foreground">
            {{ event.starts_at ? new Date(event.starts_at).toLocaleString(undefined, {
              month: 'short',
              day: 'numeric',
              hour: 'numeric',
              minute: '2-digit',
            }) : '—' }}
          </p>
        </li>
      </ul>
    </CardContent>
  </Card>
</template>
