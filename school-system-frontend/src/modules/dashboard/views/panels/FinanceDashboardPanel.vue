<script setup lang="ts">
import { onMounted } from 'vue'
import { Activity, DollarSign, Wallet, Banknote } from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import PayrollPanel from '@/components/dashboard/PayrollPanel.vue'
import ActivityChart from '@/components/dashboard/ActivityChart.vue'
import MonthlyStatsChart from '@/components/dashboard/MonthlyStatsChart.vue'
import FinanceOverviewPanel from '@/components/dashboard/FinanceOverviewPanel.vue'
import ActivityFeed from '@/components/dashboard/ActivityFeed.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { useAuth } from '@/composables/useAuth'
import { useStaffDashboard } from '@/composables/useStaffDashboard'
import { getRoleDashboardMeta } from '@/lib/role-dashboard'
import { STAFF_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis,
  activity, monthly, recent, financeSummary, load,
} = useStaffDashboard()

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { maximumFractionDigits: 0 })
}

onMounted(() => load({ analytics: true, activityFeed: true, financeSummary: true }))
</script>

<template>
  <div class="mx-auto max-w-[1400px] space-y-8 pb-8">
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

      <section aria-labelledby="finance-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="finance-kpis" class="sr-only">Finance KPIs</h2>
        <KpiCard title="Outstanding fees" :value="`$${formatMoney(kpis.outstandingFees)}`" subtitle="Unpaid student balances" :icon="DollarSign" accent="danger" :trend="kpis.paymentsGrowth" href="/finance/invoices" />
        <KpiCard title="Revenue today" :value="`$${formatMoney(kpis.paymentsToday)}`" :subtitle="`$${formatMoney(kpis.totalRevenue)} lifetime`" :icon="Activity" accent="success" :trend="kpis.revenueChange" href="/finance/payments" />
        <KpiCard title="Monthly transactions" :value="String(kpis.totalActivity)" subtitle="Ledger entries this month" :icon="Wallet" :trend="kpis.activityChange" href="/finance/transactions" />
        <KpiCard title="Payroll pending" :value="`$${formatMoney(kpis.payrollSummary.total_pending + kpis.payrollSummary.total_partial)}`" subtitle="Outstanding staff payroll" :icon="Banknote" accent="warning" href="/finance/payroll" />
      </section>

      <RoleQuickActions variant="finance" />

      <section v-if="financeSummary">
        <FinanceOverviewPanel :summary="financeSummary" :kpis="kpis" />
      </section>

      <section class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
          <MonthlyStatsChart :data="monthly" />
          <ActivityChart :data="activity" />
        </div>
        <div class="xl:col-span-4 space-y-6">
          <PayrollPanel :summary="kpis.payrollSummary" />
          <ActivityFeed :items="recent" class="min-h-[280px]" />
        </div>
      </section>

      <DashboardModulesGrid
        :groups="STAFF_DASHBOARD_MODULE_GROUPS"
        title="Your modules"
        description="Finance, operations, and admin areas available to you"
      />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="load()" />
  </div>
</template>
