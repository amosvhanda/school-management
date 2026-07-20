<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Activity, DollarSign, Wallet, Banknote } from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { useAuth } from '@/composables/useAuth'
import { useStaffDashboard } from '@/composables/useStaffDashboard'
import { lazy } from '@/lib/lazy'
import { getRoleDashboardMeta } from '@/lib/role-dashboard'
import { FINANCE_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

const PayrollPanel = lazy(() => import('@/components/dashboard/PayrollPanel.vue'))
const ActivityChart = lazy(() => import('@/components/dashboard/ActivityChart.vue'))
const MonthlyStatsChart = lazy(() => import('@/components/dashboard/MonthlyStatsChart.vue'))
const FinanceOverviewPanel = lazy(() => import('@/components/dashboard/FinanceOverviewPanel.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis,
  activity, monthly, recent, financeSummary, load,
} = useStaffDashboard()

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'Outstanding fees',
    value: `$${formatMoney(kpis.value?.outstandingFees ?? 0)}`,
    subtitle: 'Unpaid student balances',
    icon: DollarSign,
    accent: 'danger' as const,
    trend: kpis.value?.paymentsGrowth ?? 0,
    href: '/finance/invoices',
  },
  {
    title: 'Revenue today',
    value: `$${formatMoney(kpis.value?.paymentsToday ?? 0)}`,
    subtitle: `$${formatMoney(kpis.value?.totalRevenue ?? 0)} lifetime`,
    icon: Activity,
    accent: 'success' as const,
    trend: kpis.value?.revenueChange ?? 0,
    href: '/finance/payments',
  },
  {
    title: 'Monthly transactions',
    value: String(kpis.value?.totalActivity ?? 0),
    subtitle: 'Ledger entries this month',
    icon: Wallet,
    trend: kpis.value?.activityChange ?? 0,
    href: '/finance/transactions',
  },
  {
    title: 'Payroll pending',
    value: `$${formatMoney((kpis.value?.payrollSummary.total_pending ?? 0) + (kpis.value?.payrollSummary.total_partial ?? 0))}`,
    subtitle: 'Outstanding staff payroll',
    icon: Banknote,
    accent: 'warning' as const,
    href: '/finance/payroll',
  },
])

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { maximumFractionDigits: 0 })
}

onMounted(() => load({ analytics: true, activityFeed: true, financeSummary: true }))
</script>

<template>
  <div class="mx-auto max-w-screen-2xl space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      :role="meta.label"
      :subtitle="meta.subtitle"
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="load({ analytics: true, activityFeed: true, financeSummary: true })"
    />

    <DashboardSkeleton v-if="loading" />

    <template v-else-if="kpis">
      <Alert v-if="error" variant="destructive"><AlertDescription>{{ error }}</AlertDescription></Alert>

      <MetricBand title="Finance KPIs" description="Live financial snapshot for the current school year" :cards="overviewCards" />

      <RoleQuickActions variant="finance" />

      <section v-if="financeSummary">
        <FinanceOverviewPanel :summary="financeSummary" :kpis="kpis" />
      </section>

      <section class="space-y-4" aria-labelledby="finance-insights-title">
        <div>
          <h2 id="finance-insights-title" class="text-base font-semibold tracking-tight md:text-lg">Insights</h2>
          <p class="text-sm text-muted-foreground">Monthly performance, activity trend, and recent ledger events</p>
        </div>
        <div class="grid gap-6 xl:grid-cols-12">
          <div class="space-y-6 xl:col-span-8">
            <MonthlyStatsChart :data="monthly" />
            <ActivityChart :data="activity" />
          </div>
          <div class="space-y-6 xl:col-span-4">
            <PayrollPanel :summary="kpis.payrollSummary" />
            <ActivityFeed :items="recent" class="min-h-72" />
          </div>
        </div>
      </section>

      <DashboardModulesGrid
        :groups="FINANCE_DASHBOARD_MODULE_GROUPS"
        title="Your modules"
        description="Finance, operations, and admin areas available to you"
      />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="load()" />
  </div>
</template>
