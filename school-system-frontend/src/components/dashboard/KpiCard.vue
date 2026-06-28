<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { Component } from 'vue'
import { cn } from '@/lib/utils'
import TrendBadge from '@/components/dashboard/TrendBadge.vue'

defineProps<{
  title: string
  value: string | number
  subtitle?: string
  trend?: number
  trendLabel?: string
  icon?: Component
  accent?: 'default' | 'success' | 'warning' | 'danger'
  href?: string
}>()

const accentClasses = {
  default: 'bg-primary/10 text-primary',
  success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
  warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
  danger: 'bg-red-500/10 text-red-600 dark:text-red-400',
}
</script>

<template>
  <component
    :is="href ? RouterLink : 'article'"
    :to="href"
    class="group surface-card relative block overflow-hidden p-5 transition-shadow hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
    :class="href && 'cursor-pointer'"
    :aria-label="`${title}: ${value}`"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="space-y-1">
        <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{{ title }}</p>
        <p class="text-3xl font-semibold tracking-tight tabular-nums">{{ value }}</p>
      </div>
      <div
        v-if="icon"
        :class="cn(
          'flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform duration-200 group-hover:scale-105',
          accentClasses[accent ?? 'default'],
        )"
      >
        <component :is="icon" class="size-5" aria-hidden="true" />
      </div>
    </div>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-border/60 pt-3">
      <p v-if="subtitle" class="text-xs text-muted-foreground">{{ subtitle }}</p>
      <TrendBadge v-if="trend !== undefined" :value="trend" :label="trendLabel ?? 'vs last period'" />
    </div>
  </component>
</template>
