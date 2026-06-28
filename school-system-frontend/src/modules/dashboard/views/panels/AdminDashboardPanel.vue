<script setup lang="ts">
import { onMounted } from 'vue'
import {
  Activity,
  DollarSign,
  GraduationCap,
  Users,
} from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import DashboardSecondaryMetrics from '@/components/dashboard/DashboardSecondaryMetrics.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import AttendancePanel from '@/components/dashboard/AttendancePanel.vue'
import PayrollPanel from '@/components/dashboard/PayrollPanel.vue'
import ActivityChart from '@/components/dashboard/ActivityChart.vue'
import MonthlyStatsChart from '@/components/dashboard/MonthlyStatsChart.vue'
import ActivityFeed from '@/components/dashboard/ActivityFeed.vue'
import CommandCenterSection from '@/components/dashboard/CommandCenterSection.vue'
import AcademicHeatmap from '@/components/dashboard/AcademicHeatmap.vue'
import FinanceOverviewPanel from '@/components/dashboard/FinanceOverviewPanel.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { useAuth } from '@/composables/useAuth'
import { useStaffDashboard } from '@/composables/useStaffDashboard'
import { getRoleDashboardMeta } from '@/lib/role-dashboard'
import { STAFF_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

const { user, checkCapability } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, partialErrors, lastUpdated, kpis,
  activity, monthly, recent, commandCenter, financeSummary, load,
} = useStaffDashboard()

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { maximumFractionDigits: 0 })
}

onMounted(() => load({
  analytics: true,
  activityFeed: true,
  commandCenter: checkCapability('canManageTeachers'),
  financeSummary: checkCapability('canManageFinance'),
}))
</script>

<template>
  <div class="mx-auto max-w-[1600px] space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      :role="meta.label"
      :subtitle="meta.subtitle"
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="load({ analytics: true, activityFeed: true, commandCenter: true, financeSummary: true })"
    />

    <DashboardSkeleton v-if="loading" />

    <template v-else-if="kpis">
      <Alert v-if="error" variant="destructive"><AlertDescription>{{ error }}</AlertDescription></Alert>
      <Alert v-else-if="partialErrors.length" variant="destructive">
        <AlertDescription>Some widgets failed: {{ partialErrors.join(' · ') }}</AlertDescription>
      </Alert>

      <section aria-labelledby="admin-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="admin-kpis" class="sr-only">School KPIs</h2>
        <KpiCard title="Active students" :value="kpis.activeStudents" :subtitle="`${kpis.totalStudents} enrolled · ${kpis.totalClasses} classes`" :icon="GraduationCap" :trend="kpis.studentsGrowth" href="/students" />
        <KpiCard title="Teaching staff" :value="kpis.totalTeachers" :subtitle="`${kpis.totalParents} parents on file`" :icon="Users" :trend="kpis.usersChange" href="/teachers" />
        <KpiCard title="Outstanding fees" :value="`$${formatMoney(kpis.outstandingFees)}`" subtitle="Unpaid balances" :icon="DollarSign" accent="danger" :trend="kpis.paymentsGrowth" href="/finance/invoices" />
        <KpiCard title="Revenue today" :value="`$${formatMoney(kpis.paymentsToday)}`" :subtitle="`$${formatMoney(kpis.totalRevenue)} lifetime`" :icon="Activity" accent="success" :trend="kpis.revenueChange" href="/finance/payments" />
      </section>

      <DashboardSecondaryMetrics :kpis="kpis" />
      <RoleQuickActions variant="admin" />

      <section class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
          <ActivityChart :data="activity" />
          <MonthlyStatsChart :data="monthly" />
        </div>
        <div class="xl:col-span-4">
          <ActivityFeed :items="recent" class="min-h-[420px]" />
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <AttendancePanel :summary="kpis.attendanceSummary" />
        <PayrollPanel :summary="kpis.payrollSummary" />
      </section>

      <section v-if="financeSummary && checkCapability('canManageFinance')">
        <FinanceOverviewPanel :summary="financeSummary" :kpis="kpis" />
      </section>

      <section v-if="commandCenter" class="space-y-6">
        <div class="border-b pb-4">
          <h2 class="text-lg font-semibold tracking-tight">Executive overview</h2>
          <p class="text-sm text-muted-foreground">School health, risk alerts, and performance</p>
        </div>
        <CommandCenterSection :data="commandCenter" />
        <AcademicHeatmap v-if="commandCenter.academic_heatmap.length" :items="commandCenter.academic_heatmap" />
      </section>

      <DashboardModulesGrid :groups="STAFF_DASHBOARD_MODULE_GROUPS" />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="load()" />
  </div>
</template>
