<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { CreditCard, Receipt, Tags, ArrowRight, BarChart3 } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import FinanceOverviewPanel from '@/components/dashboard/FinanceOverviewPanel.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { fetchFinanceSummary, fetchKpis } from '@/services/dashboard.service'
import { formatMoney } from '@/lib/finance-constants'
import type { DashboardKpis } from '@/types/dashboard'

const loading = ref(true)
const error = ref<string | null>(null)
const summary = ref<Record<string, unknown> | null>(null)
const kpis = ref<DashboardKpis | null>(null)

const financeKpis = computed(() => ({
  outstandingFees: kpis.value?.outstandingFees ?? Number(summary.value?.totalOutstanding ?? 0),
  paymentsToday: kpis.value?.paymentsToday ?? Number(summary.value?.collectedToday ?? 0),
  totalRevenue: kpis.value?.totalRevenue ?? 0,
  paymentsGrowth: kpis.value?.paymentsGrowth ?? 0,
}))

const quickLinks = [
  { title: 'Payments', description: 'Record and review collections', href: '/finance/payments', icon: CreditCard },
  { title: 'Invoices', description: 'Bill students and track balances', href: '/finance/invoices', icon: Receipt },
  { title: 'Fee structures', description: 'Configure class fee schedules', href: '/finance/fees', icon: Tags },
  { title: 'Aging report', description: 'Outstanding balances by due date', href: '/finance/reports', icon: BarChart3 },
]

async function load() {
  loading.value = true
  error.value = null
  try {
    const [summaryResult, kpiResult] = await Promise.all([
      fetchFinanceSummary(),
      fetchKpis().catch(() => null),
    ])
    summary.value = summaryResult
    kpis.value = kpiResult
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load finance summary'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Finance overview</h1>
      <p class="text-muted-foreground">Collections, outstanding balances, and quick access to finance modules</p>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="summary">
      <FinanceOverviewPanel :summary="summary" :kpis="financeKpis" />

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card v-for="link in quickLinks" :key="link.href">
          <CardHeader class="pb-3">
            <div class="flex items-center gap-2">
              <component :is="link.icon" class="h-4 w-4 text-muted-foreground" />
              <CardTitle class="text-base">{{ link.title }}</CardTitle>
            </div>
            <CardDescription>{{ link.description }}</CardDescription>
          </CardHeader>
          <CardContent>
            <Button variant="outline" size="sm" as-child>
              <RouterLink :to="link.href">
                Open
                <ArrowRight class="ml-1 h-3.5 w-3.5" />
              </RouterLink>
            </Button>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Summary details</CardTitle>
          <CardDescription>Currency: {{ summary.currency ?? 'USD' }}</CardDescription>
        </CardHeader>
        <CardContent>
          <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <dt class="text-sm text-muted-foreground">Total invoices</dt>
              <dd class="text-lg font-semibold">{{ summary.totalInvoices ?? 0 }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Outstanding</dt>
              <dd class="text-lg font-semibold">{{ formatMoney(summary.totalOutstanding, String(summary.currency ?? 'USD')) }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Collected today</dt>
              <dd class="text-lg font-semibold">{{ formatMoney(summary.collectedToday, String(summary.currency ?? 'USD')) }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Overdue invoices</dt>
              <dd class="text-lg font-semibold text-destructive">{{ summary.overdueInvoices ?? 0 }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
