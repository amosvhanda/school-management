<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import ModuleHub from '@/components/layout/ModuleHub.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import FinanceOverviewPanel from '@/components/dashboard/FinanceOverviewPanel.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { fetchFinanceSummary, fetchKpis } from '@/services/dashboard.service'
import { formatMoney } from '@/lib/finance-constants'
import type { DashboardKpis } from '@/types/dashboard'
import { FINANCE_HUB_DEFAULT_TAB, FINANCE_HUB_TABS } from '@/modules/finance/finance-hub-tabs'

const loading = ref(true)
const error = ref<string | null>(null)
const summary = ref<Record<string, unknown> | null>(null)
const kpis = ref<DashboardKpis | null>(null)

const financeKpis = computed(() => ({
  outstandingFees: kpis.value?.outstandingFees ?? Number(summary.value?.totalOutstanding ?? 0),
  paymentsToday: kpis.value?.paymentsToday ?? Number(summary.value?.collectedToday ?? 0),
  totalRevenue: kpis.value?.totalRevenue ?? Number(summary.value?.totalRevenue ?? 0),
  paymentsGrowth: kpis.value?.paymentsGrowth ?? 0,
}))

const currency = computed(() => String(summary.value?.currency ?? 'USD'))

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
  <ModuleHub
    title="Finance"
    description="Billing, payroll, cash flow, and the school ledger — one workspace."
    route-name="finance"
    :tabs="FINANCE_HUB_TABS"
    :default-tab="FINANCE_HUB_DEFAULT_TAB"
    aria-label="Finance sections"
  >
    <template #panel="{ panel }">
      <div v-if="panel === 'finance-overview'" class="space-y-6">
        <PageLoader v-if="loading" />
        <ErrorState v-else-if="error" :description="error" @retry="load" />
        <template v-else-if="summary">
          <FinanceOverviewPanel :summary="summary" :kpis="financeKpis" />
          <Card class="border-border/70">
            <CardHeader>
              <CardTitle>Cash movement</CardTitle>
              <CardDescription>Currency: {{ currency }}</CardDescription>
            </CardHeader>
            <CardContent>
              <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                  <dt class="text-sm text-muted-foreground">Collected today</dt>
                  <dd class="text-lg font-semibold">{{ formatMoney(summary.collectedToday, currency) }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-muted-foreground">Payroll paid today</dt>
                  <dd class="text-lg font-semibold">{{ formatMoney(summary.payrollPaidToday, currency) }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-muted-foreground">Net cash today</dt>
                  <dd class="text-lg font-semibold">{{ formatMoney(summary.netCashToday, currency) }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-muted-foreground">Payroll paid this month</dt>
                  <dd class="text-lg font-semibold">{{ formatMoney(summary.payrollPaidThisMonth, currency) }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-muted-foreground">Payroll still due</dt>
                  <dd class="text-lg font-semibold">{{ formatMoney(summary.payrollPending, currency) }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-muted-foreground">Outstanding fees</dt>
                  <dd class="text-lg font-semibold">{{ formatMoney(summary.totalOutstanding, currency) }}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>
        </template>
      </div>
    </template>
  </ModuleHub>
</template>
