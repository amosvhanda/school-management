<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { Component } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { cn } from '@/lib/utils'
import { ACCENT_SOFT } from '@/lib/design-system'
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
  /** When true, treat as no-data: muted value, no misleading trend emphasis. */
  empty?: boolean
}>()

const { canOpen } = useRouteAccess()

const linkTo = computed(() => (props.href && canOpen(props.href) ? props.href : undefined))

const resolvedAccent = computed(() => (props.empty ? 'default' : (props.accent ?? 'default')))

const accentClasses = {
  default: ACCENT_SOFT.primary,
  success: ACCENT_SOFT.success,
  warning: ACCENT_SOFT.warning,
  danger: ACCENT_SOFT.danger,
} as const
</script>

<template>
  <component
    :is="linkTo ? RouterLink : 'article'"
    :to="linkTo"
    class="block h-full rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
    :aria-label="`${title}: ${value}`"
  >
    <Card
      :class="cn(
        'group h-full border-0 py-0 shadow-[var(--shadow-soft)] ring-1 ring-border/70 transition-all duration-200 motion-reduce:transition-none dark:bg-card dark:ring-border/50',
        linkTo ? 'hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)] hover:ring-primary/25 motion-reduce:hover:translate-y-0' : '',
        empty ? 'opacity-90' : '',
      )"
    >
      <CardContent class="flex items-start justify-between gap-3 p-4 sm:p-5">
        <div class="min-w-0 flex-1 space-y-2">
          <p
            :class="cn(
              'text-[1.75rem] font-medium tracking-tight tabular-nums sm:text-[1.875rem]',
              empty ? 'text-muted-foreground' : 'text-foreground',
            )"
          >
            {{ value }}
          </p>
          <div class="space-y-1">
            <p class="text-base font-medium text-foreground/85 dark:text-muted-foreground">{{ title }}</p>
            <div class="flex flex-wrap items-center gap-2">
              <p v-if="subtitle" class="text-sm leading-snug text-muted-foreground">{{ subtitle }}</p>
              <TrendBadge
                v-if="!empty && trend !== undefined"
                :value="trend"
                :label="trendLabel"
                compact
              />
            </div>
          </div>
        </div>
        <div
          v-if="icon"
          :class="cn(
            'flex size-11 shrink-0 items-center justify-center rounded-xl',
            accentClasses[resolvedAccent],
            linkTo && !empty ? 'transition-transform duration-200 group-hover:scale-105' : '',
          )"
          aria-hidden="true"
        >
          <component :is="icon" class="size-5" />
        </div>
      </CardContent>
    </Card>
  </component>
</template>
