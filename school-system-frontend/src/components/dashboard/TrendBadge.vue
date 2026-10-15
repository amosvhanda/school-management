<script setup lang="ts">
import { TrendingDown, TrendingUp } from '@lucide/vue'
import { cn } from '@/lib/utils'

withDefaults(
  defineProps<{
    value: number
    label?: string
    /** Smaller inline chip for KPI footers */
    compact?: boolean
  }>(),
  { compact: false },
)
</script>

<template>
  <div
    :class="cn(
      'inline-flex items-center gap-1 font-medium tabular-nums',
      compact ? 'text-sm' : 'rounded-md px-2 py-0.5 text-sm',
      value >= 0
        ? compact
          ? 'text-chart-2'
          : 'bg-chart-2/15 text-chart-2'
        : compact
          ? 'text-destructive'
          : 'bg-destructive/10 text-destructive',
    )"
  >
    <TrendingUp v-if="value >= 0" class="size-3 shrink-0" aria-hidden="true" />
    <TrendingDown v-else class="size-3 shrink-0" aria-hidden="true" />
    <span>{{ value >= 0 ? '+' : '' }}{{ Number.isInteger(value) ? value : value.toFixed(1) }}%</span>
    <span class="sr-only">{{ value >= 0 ? 'increase' : 'decrease' }}</span>
    <span v-if="label && !compact" class="font-normal text-muted-foreground">{{ label }}</span>
  </div>
</template>
