<script setup lang="ts">
import { computed } from 'vue'
import type { Component } from 'vue'
import { useRouter } from 'vue-router'
import DashboardSection from './DashboardSection.vue'
import KpiCard from './KpiCard.vue'
import { useAuth } from '@/composables/useAuth'
import { canShowDashboardItem } from '@/lib/dashboard-access'
import type { NavCapability } from '@/types/navigation'

export interface MetricCard {
  title: string
  value: string | number
  subtitle?: string
  trend?: number
  trendLabel?: string
  icon?: Component
  accent?: 'default' | 'success' | 'warning' | 'danger'
  href?: string
  /** Required for linked KPIs — card is hidden when the user cannot open the target. */
  capability?: NavCapability | NavCapability[]
}

const props = defineProps<{
  title: string
  description?: string
  cards: MetricCard[]
}>()

const { user } = useAuth()
const router = useRouter()

const visibleCards = computed(() =>
  props.cards.filter((card) => {
    // Display-only KPI (no link): show unless a capability is set and fails.
    if (!card.href) {
      if (card.capability == null) return true
      return canShowDashboardItem(user.value, { capability: card.capability })
    }
    // Linked KPI: must pass capability + route access. No capability = hidden.
    return canShowDashboardItem(
      user.value,
      {
        href: card.href,
        capability: card.capability,
      },
      router,
    )
  }),
)
</script>

<template>
  <section v-if="visibleCards.length" class="space-y-4" :aria-label="title">
    <DashboardSection :title="title" :description="description" />
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <KpiCard v-for="card in visibleCards" :key="card.title" v-bind="card" />
    </div>
  </section>
</template>
