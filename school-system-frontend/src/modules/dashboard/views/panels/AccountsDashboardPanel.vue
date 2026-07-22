<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Activity, ArrowLeftRight, DollarSign, Wallet } from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { useAuth } from '@/composables/useAuth'
import { useStaffDashboard } from '@/composables/useStaffDashboard'
import { lazy } from '@/lib/lazy'
import { getRoleDashboardMeta } from '@/lib/role-dashboard'

const PayrollPanel = lazy(() => import('@/components/dashboard/PayrollPanel.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis,
  recent, load,
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

function refresh() {
  return load({ activityFeed: true })
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
        title="Accounts overview"
        description="Cashflow and collections at a glance"
        :cards="overviewCards"
      />

      <RoleQuickActions variant="accounts" />

      <section class="grid gap-6 lg:grid-cols-2" aria-label="Accounts workspace">
        <PayrollPanel :summary="kpis.payrollSummary" />
        <ActivityFeed :items="recent" class="min-h-80" />
      </section>
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="refresh" />
  </div>
</template>
