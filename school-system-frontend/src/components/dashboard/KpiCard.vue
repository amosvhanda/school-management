<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { Component } from 'vue'
import { Card, CardFooter, CardHeader } from '@/components/ui/card'
import { cn } from '@/lib/utils'
import { useRouteAccess } from '@/composables/useRouteAccess'
import TrendBadge from '@/components/dashboard/TrendBadge.vue'

const props = defineProps<{
  title: string
  value: string | number
  subtitle?: string
  trend?: number
  trendLabel?: string
  icon?: Component
  accent?: 'default' | 'success' | 'warning' | 'danger'
  href?: string
}>()

const { canOpen } = useRouteAccess()

// Only linkify when the target route is actually reachable; otherwise keep the
// KPI visible but non-interactive instead of leading users to "Access denied".
const linkTo = computed(() => (props.href && canOpen(props.href) ? props.href : undefined))

const accentClasses = {
  default: 'bg-primary/10 text-primary',
  success: 'bg-chart-2/15 text-chart-2',
  warning: 'bg-chart-3/15 text-chart-3',
  danger: 'bg-destructive/10 text-destructive',
}
</script>

<template>
  <component
    :is="linkTo ? RouterLink : 'div'"
    :to="linkTo"
    class="block h-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background rounded-xl"
    :aria-label="`${title}: ${value}`"
  >
    <Card
    class="group h-full transition-colors hover:bg-muted/25"
    >
      <CardHeader class="flex flex-row items-start justify-between gap-3 space-y-0 px-5 pb-0">
        <div class="min-w-0 space-y-1.5">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{{ title }}</p>
          <p class="text-3xl font-semibold tracking-tight tabular-nums text-foreground">{{ value }}</p>
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
      </CardHeader>
      <CardFooter
        v-if="subtitle || trend !== undefined"
        class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-border/60 px-5 pt-3"
      >
        <p v-if="subtitle" class="text-xs text-muted-foreground">{{ subtitle }}</p>
        <TrendBadge v-if="trend !== undefined" :value="trend" :label="trendLabel ?? 'vs last period'" />
      </CardFooter>
    </Card>
  </component>
</template>
