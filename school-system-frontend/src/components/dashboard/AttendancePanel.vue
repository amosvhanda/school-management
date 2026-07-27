<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { Separator } from '@/components/ui/separator'
import { formatDate } from '@/lib/format'
import type { AttendanceSummary } from '@/types/dashboard'

const props = withDefaults(defineProps<{
  summary: AttendanceSummary
  /** Optional deep-link when today’s register is empty. */
  actionHref?: string
  actionLabel?: string
}>(), {
  actionHref: '/academics/attendance',
  actionLabel: 'Open attendance',
})

const rate = computed(() => {
  if (!props.summary.total) return 0
  return Math.round((props.summary.present / props.summary.total) * 100)
})

const segments = computed(() => [
  { label: 'Present', value: props.summary.present, color: 'bg-emerald-500', text: 'text-emerald-600 dark:text-emerald-400' },
  { label: 'Absent', value: props.summary.absent, color: 'bg-destructive', text: 'text-destructive' },
  { label: 'Late', value: props.summary.late, color: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400' },
  { label: 'Half day', value: props.summary.half_day ?? 0, color: 'bg-violet-500', text: 'text-violet-600 dark:text-violet-400' },
  { label: 'Excused', value: props.summary.excused, color: 'bg-sky-500', text: 'text-sky-600 dark:text-sky-400' },
])
</script>

<template>
  <Card class="h-full">
    <CardHeader class="border-b border-border/60 px-5 pb-4">
      <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
          <CardTitle class="text-base font-semibold tracking-tight">Attendance today</CardTitle>
          <CardDescription>{{ formatDate(summary.date) }}</CardDescription>
        </div>
        <Badge variant="secondary" class="font-medium tabular-nums">{{ rate }}% present</Badge>
      </div>
    </CardHeader>
    <CardContent class="space-y-5 px-5 pt-5">
      <div
        v-if="!summary.total"
        class="space-y-3 rounded-lg border border-dashed border-border/70 bg-muted/20 px-3 py-3"
        role="status"
      >
        <p class="text-sm text-muted-foreground">
          No attendance marked for today yet.
        </p>
        <Button v-if="actionHref" variant="outline" size="sm" as-child>
          <RouterLink :to="actionHref">{{ actionLabel }}</RouterLink>
        </Button>
      </div>

      <div class="space-y-2">
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted-foreground">Present rate</span>
          <span class="font-medium tabular-nums">{{ summary.present }} / {{ summary.total || 0 }}</span>
        </div>
        <Progress :model-value="rate" class="h-2.5" aria-label="Present rate" />
      </div>

      <div
        class="flex h-3 overflow-hidden rounded-full bg-muted"
        role="img"
        :aria-label="`Attendance breakdown: ${segments.map((s) => `${s.label} ${s.value}`).join(', ')}`"
      >
        <div
          v-for="seg in segments.filter((s) => s.value > 0)"
          :key="seg.label"
          :class="seg.color"
          :style="{ width: summary.total ? `${(seg.value / summary.total) * 100}%` : '0%' }"
          :title="`${seg.label}: ${seg.value}`"
        />
      </div>

      <Separator />

      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div
          v-for="seg in segments"
          :key="seg.label"
          class="rounded-lg border border-border/70 bg-muted/20 p-3"
        >
          <div class="flex items-center gap-2">
            <span :class="['size-2 rounded-full', seg.color]" aria-hidden="true" />
            <span class="text-xs font-medium text-muted-foreground">{{ seg.label }}</span>
          </div>
          <p :class="['mt-1.5 text-xl font-semibold tracking-tight tabular-nums', seg.text]">
            {{ seg.value }}
          </p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
