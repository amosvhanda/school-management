<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import EmptyState from '@/components/feedback/EmptyState.vue'
import type { SchoolDashboardAdmissionSlice } from '@/types/dashboard'

const props = defineProps<{
  totalStudents: number
  studentsGrowth?: number
  slices: SchoolDashboardAdmissionSlice[]
  classCount?: number
}>()

const totalFromSlices = computed(() =>
  props.slices.reduce((sum, slice) => sum + Number(slice.value ?? 0), 0),
)

const rows = computed(() => {
  const max = Math.max(totalFromSlices.value, 1)
  const palettes = ['bg-primary', 'bg-chart-4', 'bg-chart-2', 'bg-chart-3', 'bg-chart-5']
  return props.slices.map((slice, index) => {
    const value = Number(slice.value ?? 0)
    const pct = Math.round((value / max) * 1000) / 10
    return {
      label: slice.label,
      value,
      pct,
      bar: palettes[index % palettes.length],
    }
  })
})

const avgClassSize = computed(() => {
  const classes = Number(props.classCount ?? 0)
  if (classes <= 0) return null
  return Math.round(props.totalStudents / classes)
})

const growthLabel = computed(() => {
  const value = props.studentsGrowth
  if (value == null || Number.isNaN(value) || value === 0) return null
  const sign = value > 0 ? '+' : ''
  return `${sign}${value}% YoY`
})
</script>

<template>
  <Card class="h-full">
    <CardHeader class="px-5 pb-2">
      <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Capacity</p>
      <CardTitle class="text-base">Class-Wise Distribution</CardTitle>
      <CardDescription>Enrolment by class from this school’s live records</CardDescription>
    </CardHeader>
    <CardContent class="space-y-5 px-5">
      <div class="flex items-end justify-between gap-3">
        <div>
          <p class="text-3xl font-semibold tabular-nums">{{ totalStudents.toLocaleString() }}</p>
          <p class="text-xs text-muted-foreground">Total students enrolled</p>
        </div>
        <p v-if="growthLabel" class="text-xs font-medium text-chart-2">{{ growthLabel }}</p>
      </div>

      <EmptyState
        v-if="!rows.length"
        variant="embedded"
        title="No class breakdown yet"
        description="Assign learners to classes to see this distribution."
      />

      <ul v-else class="space-y-3" aria-label="Enrolment by class">
        <li v-for="row in rows" :key="row.label" class="space-y-1.5">
          <div class="flex items-center justify-between gap-2 text-sm">
            <span class="truncate text-muted-foreground">{{ row.label }}</span>
            <span class="shrink-0 tabular-nums">{{ row.value.toLocaleString() }} · {{ row.pct }}%</span>
          </div>
          <div class="h-2 overflow-hidden rounded-full bg-muted" aria-hidden="true">
            <div class="h-full rounded-full" :class="row.bar" :style="{ width: `${Math.max(row.pct, 2)}%` }" />
          </div>
        </li>
      </ul>

      <dl class="grid grid-cols-2 gap-3 border-t border-border/70 pt-4 text-sm">
        <div>
          <dt class="text-xs text-muted-foreground">Classes</dt>
          <dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ classCount ?? 0 }}</dd>
        </div>
        <div>
          <dt class="text-xs text-muted-foreground">Avg. class size</dt>
          <dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ avgClassSize ?? '—' }}</dd>
        </div>
      </dl>
    </CardContent>
  </Card>
</template>
