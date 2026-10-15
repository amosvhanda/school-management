<script setup lang="ts">
import { computed } from 'vue'
import {
  Award,
  BookOpen,
  CalendarCheck2,
  ClipboardList,
  FileText,
  History,
  UserPlus,
  Wallet,
} from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatDate } from '@/lib/format'
import { STATUS_TONE_ICON } from '@/lib/ui-status'
import { cn } from '@/lib/utils'

export type TimelineEvent = {
  id: string
  date?: string | null
  title: string
  description?: string
  tone: keyof typeof STATUS_TONE_ICON
  icon: typeof History
}

const props = defineProps<{
  events: TimelineEvent[]
}>()

const sorted = computed(() =>
  [...props.events].sort((a, b) => String(b.date ?? '').localeCompare(String(a.date ?? ''))),
)
</script>

<template>
  <Card>
    <CardHeader class="border-b border-border/50 px-5 py-4">
      <CardTitle class="flex items-center gap-2 text-base">
        <History class="size-4 text-primary" aria-hidden="true" />
        Activity timeline
      </CardTitle>
    </CardHeader>
    <CardContent class="p-5">
      <ol v-if="sorted.length" class="relative space-y-6 border-l border-border/70 pl-6">
        <li v-for="event in sorted" :key="event.id" class="relative">
          <span
            :class="cn(
              'absolute -left-[1.875rem] flex size-8 items-center justify-center rounded-full ring-4 ring-card',
              STATUS_TONE_ICON[event.tone],
            )"
            aria-hidden="true"
          >
            <component :is="event.icon" class="size-3.5" />
          </span>
          <p class="text-xs text-muted-foreground">{{ formatDate(event.date) }}</p>
          <p class="text-sm font-semibold text-foreground">{{ event.title }}</p>
          <p v-if="event.description" class="text-sm text-muted-foreground">{{ event.description }}</p>
        </li>
      </ol>
      <EmptyState
        v-else
        variant="embedded"
        title="No timeline events yet"
        description="Admissions, fees, documents, and academic updates will build this timeline."
      />
    </CardContent>
  </Card>
</template>

<script lang="ts">
export const timelineIcons = {
  Award,
  BookOpen,
  CalendarCheck2,
  ClipboardList,
  FileText,
  History,
  UserPlus,
  Wallet,
}
</script>
