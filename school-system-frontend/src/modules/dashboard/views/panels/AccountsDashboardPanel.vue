<script setup lang="ts">
import { onMounted } from 'vue'
import { Activity, ArrowLeftRight, DollarSign, Wallet } from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import PayrollPanel from '@/components/dashboard/PayrollPanel.vue'
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
  recent, financeSummary, load,
} = useStaffDashboard()

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { maximumFractionDigits: 0 })
}

onMounted(() => load({ activityFeed: true, financeSummary: true }))
</script>

<template>
  <div class="mx-auto max-w-[1400px] space-y-8 pb-8">
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

      <section aria-labelledby="accounts-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="accounts-kpis" class="sr-only">Accounts KPIs</h2>
        <KpiCard title="Revenue today" :value="`$${formatMoney(kpis.paymentsToday)}`" subtitle="Collections recorded today" :icon="DollarSign" accent="success" href="/finance/payments" />
        <KpiCard title="Outstanding fees" :value="`$${formatMoney(kpis.outstandingFees)}`" subtitle="Unpaid balances" :icon="Wallet" accent="danger" href="/finance/invoices" />
        <KpiCard title="Transactions" :value="String(kpis.totalActivity)" subtitle="This month" :icon="ArrowLeftRight" href="/finance/transactions" />
        <KpiCard title="Payroll due" :value="`$${formatMoney(kpis.payrollSummary.total_pending)}`" subtitle="Pending staff payments" :icon="Activity" accent="warning" href="/finance/payroll" />
      </section>

      <RoleQuickActions variant="accounts" />

      <section v-if="financeSummary">
        <FinanceOverviewPanel :summary="financeSummary" :kpis="kpis" />
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <PayrollPanel :summary="kpis.payrollSummary" />
        <ActivityFeed :items="recent" class="min-h-[320px]" />
      </section>

      <DashboardModulesGrid
        :groups="STAFF_DASHBOARD_MODULE_GROUPS"
        title="Your modules"
        description="Accounting, finance, and related school areas"
      />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="load()" />
  </div>
</template>
