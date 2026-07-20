<script setup lang="ts">
import { computed, onMounted } from 'vue'
import {
  Activity,
  ClipboardList,
  DollarSign,
  GraduationCap,
  Palmtree,
  Users,
} from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { useAuth } from '@/composables/useAuth'
import { useStaffDashboard } from '@/composables/useStaffDashboard'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import { lazy } from '@/lib/lazy'
import { getRoleDashboardMeta } from '@/lib/role-dashboard'
import { STAFF_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

const DashboardSecondaryMetrics = lazy(() => import('@/components/dashboard/DashboardSecondaryMetrics.vue'))
const AttendancePanel = lazy(() => import('@/components/dashboard/AttendancePanel.vue'))
const PayrollPanel = lazy(() => import('@/components/dashboard/PayrollPanel.vue'))
const ActivityChart = lazy(() => import('@/components/dashboard/ActivityChart.vue'))
const MonthlyStatsChart = lazy(() => import('@/components/dashboard/MonthlyStatsChart.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))
const CommandCenterSection = lazy(() => import('@/components/dashboard/CommandCenterSection.vue'))
const AcademicHeatmap = lazy(() => import('@/components/dashboard/AcademicHeatmap.vue'))
const FinanceOverviewPanel = lazy(() => import('@/components/dashboard/FinanceOverviewPanel.vue'))

const { user, checkCapability } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, partialErrors, lastUpdated, kpis,
  activity, monthly, recent, commandCenter, financeSummary, load,
} = useStaffDashboard()

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'Active students',
    value: kpis.value?.activeStudents ?? 0,
    subtitle: `${kpis.value?.totalStudents ?? 0} enrolled · ${kpis.value?.totalClasses ?? 0} classes`,
    icon: GraduationCap,
    trend: kpis.value?.studentsGrowth ?? 0,
    href: '/students',
  },
  {
    title: 'Teaching staff',
    value: kpis.value?.totalTeachers ?? 0,
    subtitle: `${kpis.value?.totalParents ?? 0} parents on file`,
    icon: Users,
    trend: kpis.value?.usersChange ?? 0,
    href: '/teachers',
  },
  {
    title: 'Outstanding fees',
    value: `$${formatMoney(kpis.value?.outstandingFees ?? 0)}`,
    subtitle: 'Unpaid balances',
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
    title: 'Pending enrollments',
    value: kpis.value?.pendingEnrollments ?? 0,
    subtitle: 'Applications awaiting approval',
    icon: ClipboardList,
    accent: (kpis.value?.pendingEnrollments ?? 0) > 0 ? 'warning' as const : undefined,
    href: '/enrollment',
  },
  {
    title: 'Pending leave',
    value: kpis.value?.pendingLeaveRequests ?? 0,
    subtitle: 'Staff leave to review',
    icon: Palmtree,
    accent: (kpis.value?.pendingLeaveRequests ?? 0) > 0 ? 'warning' as const : undefined,
    href: '/hr/leave',
  },
])

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
  <div class="mx-auto max-w-[1600px] space-y-8 pb-10">
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

      <MetricBand title="School KPIs" description="Quick health checks for the current campus state" :cards="overviewCards" />

      <DashboardSecondaryMetrics :kpis="kpis" />
      <RoleQuickActions variant="admin" />

      <section class="space-y-4" aria-labelledby="insights-title">
        <div>
          <h2 id="insights-title" class="text-base font-semibold tracking-tight md:text-lg">Insights</h2>
          <p class="text-sm text-muted-foreground">Trends, monthly performance, and recent school activity</p>
        </div>
        <div class="grid gap-6 xl:grid-cols-12">
          <div class="space-y-6 xl:col-span-8">
            <ActivityChart :data="activity" />
            <MonthlyStatsChart :data="monthly" />
          </div>
          <div class="xl:col-span-4">
            <ActivityFeed :items="recent" class="min-h-105" />
          </div>
        </div>
      </section>

      <section class="space-y-4" aria-labelledby="ops-title">
        <div>
          <h2 id="ops-title" class="text-base font-semibold tracking-tight md:text-lg">Daily operations</h2>
          <p class="text-sm text-muted-foreground">Attendance and payroll at a glance</p>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
          <AttendancePanel :summary="kpis.attendanceSummary" />
          <PayrollPanel :summary="kpis.payrollSummary" />
        </div>
      </section>

      <section v-if="financeSummary && checkCapability('canManageFinance')">
        <FinanceOverviewPanel :summary="financeSummary" :kpis="kpis" />
      </section>

      <section v-if="commandCenter" class="space-y-6">
        <div>
          <h2 class="text-base font-semibold tracking-tight md:text-lg">Executive overview</h2>
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
