<script setup lang="ts">
import { cn } from '@/lib/utils'

defineProps<{
  label: string
  value?: string | number | null
  class?: string
}>()
</script>

<template>
  <div :class="cn('space-y-1.5', $props.class)">
    <p class="text-xs font-medium text-muted-foreground">{{ label }}</p>
    <div
      class="flex min-h-10 items-center rounded-xl bg-muted/45 px-3 py-2 text-sm font-medium text-foreground ring-1 ring-border/40 dark:bg-muted/40"
    >
      <span class="truncate">{{ value == null || value === '' ? '—' : value }}</span>
    </div>
  </div>
</template>
