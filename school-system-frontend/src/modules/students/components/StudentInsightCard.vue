<script setup lang="ts">
import type { Component } from 'vue'
import { cn } from '@/lib/utils'

withDefaults(
  defineProps<{
    title: string
    value: string
    hint?: string
    icon: Component
    tone?: 'default' | 'success' | 'warning' | 'danger' | 'info'
    hintTone?: 'default' | 'danger' | 'success'
  }>(),
  {
    tone: 'default',
    hintTone: 'default',
  },
)

const iconTone = {
  default: 'bg-primary/10 text-primary',
  success: 'bg-chart-2/15 text-chart-2',
  warning: 'bg-chart-3/15 text-chart-3',
  danger: 'bg-destructive/10 text-destructive',
  info: 'bg-chart-4/15 text-chart-4',
} as const

const hintClass = {
  default: 'text-muted-foreground',
  danger: 'text-destructive',
  success: 'text-chart-2',
} as const
</script>

<template>
  <article
    class="flex min-w-[9.5rem] flex-1 flex-col gap-3 rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/70 dark:ring-border/50"
  >
    <div class="flex items-start justify-between gap-2">
      <div
        :class="cn('flex size-9 items-center justify-center rounded-xl', iconTone[tone])"
        aria-hidden="true"
      >
        <component :is="icon" class="size-4" />
      </div>
    </div>
    <div class="space-y-1">
      <p class="text-xs font-medium text-muted-foreground">{{ title }}</p>
      <p class="text-lg font-semibold tracking-tight tabular-nums text-foreground">{{ value }}</p>
      <p v-if="hint" :class="cn('text-xs leading-snug', hintClass[hintTone])">{{ hint }}</p>
    </div>
  </article>
</template>
