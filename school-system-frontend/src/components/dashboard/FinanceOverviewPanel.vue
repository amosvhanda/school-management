<script setup lang="ts">
import { computed } from 'vue'
import { Banknote, Receipt, TrendingDown, Wallet } from 'lucide-vue-next'
import KpiCard from './KpiCard.vue'
import { formatMoney } from '@/lib/finance-constants'

const props = defineProps<{
  summary: Record<string, unknown>
  kpis: {
    outstandingFees: number
    paymentsToday: number
    totalRevenue: number
    paymentsGrowth: number
  }
}>()

const currency = computed(() => String(props.summary.currency ?? 'USD'))
</script>

<template>
  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <KpiCard
      title="Outstanding fees"
      :value="formatMoney(kpis.outstandingFees, currency)"
      subtitle="Unpaid invoice balances"
      :icon="TrendingDown"
      accent="danger"
      :trend="kpis.paymentsGrowth"
    />
    <KpiCard
      title="Collected today"
      :value="formatMoney(kpis.paymentsToday, currency)"
      subtitle="Completed payments"
      :icon="Wallet"
      accent="success"
    />
    <KpiCard
      title="Total revenue"
      :value="formatMoney(kpis.totalRevenue, currency)"
      subtitle="All time collections"
      :icon="Banknote"
    />
    <KpiCard
      title="Pending invoices"
      :value="Number(summary.pendingInvoices ?? 0).toLocaleString()"
      :subtitle="`${Number(summary.overdueInvoices ?? 0).toLocaleString()} overdue`"
      :icon="Receipt"
      accent="warning"
    />
  </div>
</template>
