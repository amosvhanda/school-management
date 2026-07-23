<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Activity, Banknote, DollarSign, Wallet } from '@lucide/vue'
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
import { getDashboardModuleGroupsForVariant, getRoleDashboardMeta } from '@/lib/role-dashboard'

const PayrollPanel = lazy(() => import('@/components/dashboard/PayrollPanel.vue'))
const MonthlyStatsChart = lazy(() => import('@/components/dashboard/MonthlyStatsChart.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis,
  monthly, recent, load,
} = useStaffDashboard()

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'Outstanding fees',
    value: `$${formatMoney(kpis.value?.outstandingFees ?? 0)}`,
    subtitle: 'Unpaid student balances',
    icon: DollarSign,
    accent: 'danger' as const,
    trend: kpis.value?.paymentsGrowth ?? 0,
    href: '/finance?tab=invoices',
    capability: 'canManageFinance',
  },
  {
    title: 'Revenue today',
    value: `$${formatMoney(kpis.value?.paymentsToday ?? 0)}`,
    subtitle: `$${formatMoney(kpis.value?.totalRevenue ?? 0)} lifetime`,
    icon: Activity,
    accent: 'success' as const,
    trend: kpis.value?.revenueChange ?? 0,
    href: '/finance?tab=payments',
    capability: 'canManageFinance',
  },
  {
    title: 'Monthly transactions',
    value: String(kpis.value?.totalActivity ?? 0),
    subtitle: 'Ledger entries this month',
    icon: Wallet,
    trend: kpis.value?.activityChange ?? 0,
    href: '/finance?tab=transactions',
    capability: 'canManageFinance',
  },
  {
    title: 'Payroll pending',
    value: `$${formatMoney((kpis.value?.payrollSummary.total_pending ?? 0) + (kpis.value?.payrollSummary.total_partial ?? 0))}`,
    subtitle: 'Outstanding staff payroll',
    icon: Banknote,
    accent: 'warning' as const,
    href: '/finance?tab=payroll',
    capability: 'canManageFinance',
  },
])

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { maximumFractionDigits: 0 })
}

function refresh() {
  return load({ analytics: true, activityFeed: true })
}

onMounted(refresh)
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      :role="meta.label"
      :subtitle="meta.subtitle"
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="refresh"
    />

    <DashboardSkeleton v-if="loading" />

    <template v-else-if="kpis">
      <Alert v-if="error" variant="destructive">
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <MetricBand
        title="Finance overview"
        description="Collections, ledger activity, and payroll"
        :cards="overviewCards"
      />

      <RoleQuickActions variant="finance" />

      <DashboardModulesGrid
        :groups="getDashboardModuleGroupsForVariant('finance')"
        title="Finance modules"
        description="Collections, payroll, and reporting for your profile"
      />

      <section class="space-y-4" aria-labelledby="finance-work-title">
        <div>
          <h2 id="finance-work-title" class="text-base font-semibold tracking-tight md:text-lg">
            Workbench
          </h2>
          <p class="text-sm text-muted-foreground">Monthly performance and payroll status</p>
        </div>
        <div class="grid gap-6 xl:grid-cols-12">
          <div class="xl:col-span-8">
            <MonthlyStatsChart :data="monthly" />
          </div>
          <div class="space-y-6 xl:col-span-4">
            <PayrollPanel :summary="kpis.payrollSummary" />
            <ActivityFeed :items="recent" class="min-h-72" />
          </div>
        </div>
      </section>
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="refresh" />
  </div>
</template>
