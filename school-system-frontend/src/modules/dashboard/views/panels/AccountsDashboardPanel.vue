<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Activity, ArrowLeftRight, DollarSign, Wallet } from '@lucide/vue'
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
import { ACCOUNTS_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

const PayrollPanel = lazy(() => import('@/components/dashboard/PayrollPanel.vue'))
const FinanceOverviewPanel = lazy(() => import('@/components/dashboard/FinanceOverviewPanel.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis,
  recent, financeSummary, load,
} = useStaffDashboard()

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'Revenue today',
    value: `$${formatMoney(kpis.value?.paymentsToday ?? 0)}`,
    subtitle: 'Collections recorded today',
    icon: DollarSign,
    accent: 'success' as const,
    href: '/finance/payments',
  },
  {
    title: 'Outstanding fees',
    value: `$${formatMoney(kpis.value?.outstandingFees ?? 0)}`,
    subtitle: 'Unpaid balances',
    icon: Wallet,
    accent: 'danger' as const,
    href: '/finance/invoices',
  },
  {
    title: 'Transactions',
    value: String(kpis.value?.totalActivity ?? 0),
    subtitle: 'This month',
    icon: ArrowLeftRight,
    href: '/finance/transactions',
  },
  {
    title: 'Payroll due',
    value: `$${formatMoney(kpis.value?.payrollSummary.total_pending ?? 0)}`,
    subtitle: 'Pending staff payments',
    icon: Activity,
    accent: 'warning' as const,
    href: '/finance/payroll',
  },
])

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { maximumFractionDigits: 0 })
}

onMounted(() => load({ activityFeed: true, financeSummary: true }))
</script>

<template>
  <div class="mx-auto max-w-350 space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      :role="meta.label"
      :subtitle="meta.subtitle"
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="load({ activityFeed: true, financeSummary: true })"
    />

    <DashboardSkeleton v-if="loading" />

    <template v-else-if="kpis">
      <Alert v-if="error" variant="destructive"><AlertDescription>{{ error }}</AlertDescription></Alert>

      <MetricBand title="Accounts KPIs" description="Cashflow and collections at a glance" :cards="overviewCards" />

      <RoleQuickActions variant="accounts" />

      <section v-if="financeSummary">
        <FinanceOverviewPanel :summary="financeSummary" :kpis="kpis" />
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <PayrollPanel :summary="kpis.payrollSummary" />
        <ActivityFeed :items="recent" class="min-h-80" />
      </section>

      <DashboardModulesGrid
        :groups="ACCOUNTS_DASHBOARD_MODULE_GROUPS"
        title="Your modules"
        description="Accounting, finance, and related school areas"
      />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="load()" />
  </div>
</template>
