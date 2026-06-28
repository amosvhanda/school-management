<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { formatDate } from '@/lib/format'
import type { AttendanceSummary } from '@/types/dashboard'

const props = defineProps<{ summary: AttendanceSummary }>()

const rate = computed(() => {
  if (!props.summary.total) return 0
  return Math.round((props.summary.present / props.summary.total) * 100)
})

const segments = computed(() => [
  { label: 'Present', value: props.summary.present, color: 'bg-emerald-500' },
  { label: 'Absent', value: props.summary.absent, color: 'bg-destructive' },
  { label: 'Late', value: props.summary.late, color: 'bg-amber-500' },
  { label: 'Excused', value: props.summary.excused, color: 'bg-blue-500' },
])
</script>

<template>
  <Card class="bg-card text-card-foreground shadow-sm">
    <CardHeader class="border-b border-border/60 pb-4">
      <div class="flex items-center justify-between gap-4">
        <div>
          <CardTitle class="text-base font-semibold tracking-tight">Attendance today</CardTitle>
          <CardDescription class="text-xs">{{ formatDate(summary.date) }}</CardDescription>
        </div>
        <Badge variant="secondary" class="font-medium">{{ rate }}% present</Badge>
      </div>
    </CardHeader>
    <CardContent class="space-y-4 pt-6">
      <div class="flex h-3 overflow-hidden rounded-full bg-muted">
        <div
          v-for="seg in segments.filter((s) => s.value > 0)"
          :key="seg.label"
          :class="seg.color"
          :style="{ width: `${(seg.value / summary.total) * 100}%` }"
          :title="`${seg.label}: ${seg.value}`"
        />
      </div>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div v-for="seg in segments" :key="seg.label" class="rounded-lg border border-muted/80 p-3 bg-muted/5">
          <div class="flex items-center gap-2">
            <span :class="['size-2 rounded-full', seg.color]" />
            <span class="text-xs text-muted-foreground font-medium">{{ seg.label }}</span>
          </div>
          <p class="mt-1 text-xl font-bold tracking-tight text-foreground">{{ seg.value }}</p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
