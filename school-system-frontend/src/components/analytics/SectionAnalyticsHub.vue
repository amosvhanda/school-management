<script setup lang="ts">
import { RouterLink } from 'vue-router'
import * as icons from '@lucide/vue'
import { ArrowUpRight } from '@lucide/vue'
import type { Component } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import type { SectionQuickLink } from '@/lib/section-hubs'

export interface SectionKpi {
  title: string
  value: string
  subtitle?: string
  icon?: Component
  accent?: 'success' | 'warning' | 'danger'
  href?: string
}

defineProps<{
  title: string
  description: string
  kpis: SectionKpi[]
  quickLinks: SectionQuickLink[]
  loading?: boolean
  error?: string | null
}>()

defineEmits<{ retry: [] }>()

function resolveIcon(name: string) {
  return (icons as Record<string, unknown>)[name] as Component ?? icons.Circle
}
</script>

<template>
  <PageShell :title="title" :description="description" max-width="wide">
    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="$emit('retry')" />

    <div v-else class="space-y-8">
      <section aria-labelledby="section-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="section-kpis" class="sr-only">Key metrics</h2>
        <KpiCard
          v-for="kpi in kpis"
          :key="kpi.title"
          :title="kpi.title"
          :value="kpi.value"
          :subtitle="kpi.subtitle"
          :icon="kpi.icon"
          :accent="kpi.accent"
          :href="kpi.href"
        />
      </section>

      <section aria-labelledby="section-shortcuts">
        <div class="mb-4">
          <h2 id="section-shortcuts" class="text-sm font-semibold tracking-tight">Shortcuts</h2>
          <p class="text-xs text-muted-foreground">Jump straight to common tasks in this section</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <RouterLink
            v-for="link in quickLinks"
            :key="link.href"
            :to="link.href"
            class="group surface-card flex flex-col gap-3 p-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <component :is="resolveIcon(link.icon)" class="size-4" aria-hidden="true" />
              </div>
              <ArrowUpRight
                class="size-3.5 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                aria-hidden="true"
              />
            </div>
            <div>
              <p class="text-sm font-medium leading-none">{{ link.title }}</p>
              <p class="mt-1 text-xs text-muted-foreground">{{ link.description }}</p>
            </div>
          </RouterLink>
        </div>
      </section>

      <section v-if="$slots.default" aria-labelledby="section-details">
        <h2 id="section-details" class="sr-only">Detailed analytics</h2>
        <slot />
      </section>
    </div>
  </PageShell>
</template>
