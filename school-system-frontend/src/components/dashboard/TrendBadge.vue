<script setup lang="ts">
import { TrendingDown, TrendingUp } from '@lucide/vue'
import { cn } from '@/lib/utils'

defineProps<{ value: number; label?: string }>()
</script>

<template>
  <div
    :class="cn(
      'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium',
      value >= 0
        ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
        : 'bg-red-500/10 text-red-700 dark:text-red-400',
    )"
  >
    <TrendingUp v-if="value >= 0" class="size-3" aria-hidden="true" />
    <TrendingDown v-else class="size-3" aria-hidden="true" />
    <span>{{ value >= 0 ? '+' : '' }}{{ value.toFixed(1) }}%</span>
    <span class="sr-only">{{ value >= 0 ? 'increase' : 'decrease' }}</span>
    <span v-if="label" class="font-normal text-muted-foreground">{{ label }}</span>
  </div>
</template>
