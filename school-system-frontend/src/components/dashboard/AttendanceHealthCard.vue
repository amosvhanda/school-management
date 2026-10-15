<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { AttendanceSummary } from '@/types/dashboard'

const props = defineProps<{
  attendance: AttendanceSummary | null
  totalStudents: number
}>()

const total = computed(() => Number(props.attendance?.total ?? 0))
const present = computed(() => Number(props.attendance?.present ?? 0))
const late = computed(() => Number(props.attendance?.late ?? 0))
const absent = computed(() => Number(props.attendance?.absent ?? 0))
const excused = computed(() => Number(props.attendance?.excused ?? 0) + Number(props.attendance?.half_day ?? 0))

const healthy = computed(() => (total.value > 0 ? Math.round((present.value / total.value) * 1000) / 10 : 0))

const segments = computed(() => {
  const safeTotal = Math.max(total.value, 1)
  return [
    { label: 'Present', value: present.value, class: 'bg-chart-2', pct: Math.round((present.value / safeTotal) * 1000) / 10 },
    { label: 'Late', value: late.value, class: 'bg-chart-3', pct: Math.round((late.value / safeTotal) * 1000) / 10 },
    { label: 'Absent', value: absent.value, class: 'bg-destructive/80', pct: Math.round((absent.value / safeTotal) * 1000) / 10 },
    { label: 'Excused', value: excused.value, class: 'bg-primary/70', pct: Math.round((excused.value / safeTotal) * 1000) / 10 },
  ]
})
</script>

<template>
  <Card class="h-full">
    <CardHeader class="px-5 pb-2">
      <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Heat</p>
      <CardTitle class="text-base">Attendance Health</CardTitle>
      <CardDescription>
        Today’s register compared with enrolled learners — not a synthetic risk plot.
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-5 px-5">
      <div class="flex items-end justify-between gap-3">
        <div>
          <p class="text-3xl font-semibold tabular-nums">{{ totalStudents.toLocaleString() }}</p>
          <p class="text-xs text-muted-foreground">Students enrolled</p>
        </div>
        <p class="rounded-full bg-chart-2/15 px-2.5 py-1 text-xs font-medium text-chart-2">
          {{ healthy }}% present
        </p>
      </div>

      <div
        class="flex h-3 overflow-hidden rounded-full bg-muted"
        role="img"
        :aria-label="segments.map((s) => `${s.label} ${s.value}`).join(', ')"
      >
        <div
          v-for="seg in segments.filter((s) => s.value > 0)"
          :key="seg.label"
          :class="seg.class"
          :style="{ width: `${Math.max(seg.pct, 2)}%` }"
        />
      </div>

      <ul class="grid gap-2 sm:grid-cols-2" aria-label="Attendance breakdown">
        <li
          v-for="seg in segments"
          :key="seg.label"
          class="flex items-center justify-between rounded-xl bg-muted/40 px-3 py-2 text-sm"
        >
          <span class="flex items-center gap-2">
            <span class="size-2 rounded-full" :class="seg.class" aria-hidden="true" />
            {{ seg.label }}
          </span>
          <span class="tabular-nums">{{ seg.value }} · {{ seg.pct }}%</span>
        </li>
      </ul>
    </CardContent>
  </Card>
</template>
