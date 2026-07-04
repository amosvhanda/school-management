<script setup lang="ts">
import type { Component } from 'vue'
import KpiCard from './KpiCard.vue'

export interface MetricCard {
  title: string
  value: string | number
  subtitle?: string
  trend?: number
  trendLabel?: string
  icon?: Component
  accent?: 'default' | 'success' | 'warning' | 'danger'
  href?: string
}

defineProps<{
  title: string
  description?: string
  cards: MetricCard[]
}>()
</script>

<template>
  <section class="space-y-4 rounded-3xl border border-border/60 bg-card/95 p-6 shadow-sm backdrop-blur">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-muted-foreground">{{ title }}</p>
        <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
      </div>
      <div class="h-px flex-1 bg-linear-to-r from-transparent via-border/80 to-transparent sm:ml-6" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <KpiCard v-for="card in cards" :key="card.title" v-bind="card" />
    </div>
  </section>
</template>
