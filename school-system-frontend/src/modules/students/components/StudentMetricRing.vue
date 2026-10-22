<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    label: string
    valueLabel: string
    /** 0–100 for the ring fill */
    percent: number
    tone?: 'primary' | 'success' | 'warning'
  }>(),
  { tone: 'primary' },
)

const clamped = computed(() => Math.max(0, Math.min(100, props.percent)))
const radius = 36
const circumference = 2 * Math.PI * radius
const dashOffset = computed(() => circumference - (clamped.value / 100) * circumference)

const stroke = {
  primary: 'stroke-primary',
  success: 'stroke-chart-2',
  warning: 'stroke-chart-3',
} as const
</script>

<template>
  <div class="flex flex-col items-center gap-2">
    <div class="relative size-[5.5rem]" role="img" :aria-label="`${label}: ${valueLabel}`">
      <svg class="size-full -rotate-90" viewBox="0 0 88 88" aria-hidden="true">
        <circle
          cx="44"
          cy="44"
          :r="radius"
          fill="none"
          class="stroke-muted"
          stroke-width="7"
        />
        <circle
          cx="44"
          cy="44"
          :r="radius"
          fill="none"
          :class="cn(stroke[tone], 'transition-[stroke-dashoffset] duration-500')"
          stroke-width="7"
          stroke-linecap="round"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="dashOffset"
        />
      </svg>
      <div class="absolute inset-0 flex items-center justify-center">
        <span class="text-sm font-semibold tabular-nums text-foreground">{{ valueLabel }}</span>
      </div>
    </div>
    <p class="max-w-[6.5rem] text-center text-xs font-medium text-muted-foreground">{{ label }}</p>
  </div>
</template>
