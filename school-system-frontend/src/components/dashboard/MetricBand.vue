<script setup lang="ts">
import type { Component } from 'vue'
import DashboardSection from './DashboardSection.vue'
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
  <section class="space-y-4" :aria-label="title">
    <DashboardSection :title="title" :description="description" />
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <KpiCard v-for="card in cards" :key="card.title" v-bind="card" />
    </div>
  </section>
</template>
