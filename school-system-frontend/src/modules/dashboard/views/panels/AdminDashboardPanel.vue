<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ClipboardList,
  DollarSign,
  GraduationCap,
  Users,
} from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { useStaffDashboard } from '@/composables/useStaffDashboard'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import { lazy } from '@/lib/lazy'
import { getRoleDashboardMeta } from '@/lib/role-dashboard'

const AttendancePanel = lazy(() => import('@/components/dashboard/AttendancePanel.vue'))
const PayrollPanel = lazy(() => import('@/components/dashboard/PayrollPanel.vue'))
const ActivityChart = lazy(() => import('@/components/dashboard/ActivityChart.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))
const CommandCenterSection = lazy(() => import('@/components/dashboard/CommandCenterSection.vue'))

const { user, checkCapability } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, partialErrors, lastUpdated, kpis,
  activity, recent, commandCenter, load,
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
    subtitle: `$${formatMoney(kpis.value?.paymentsToday ?? 0)} collected today`,
    icon: DollarSign,
    accent: 'danger' as const,
    trend: kpis.value?.paymentsGrowth ?? 0,
    href: '/finance/invoices',
  },
  {
    title: 'Pending enrollments',
    value: kpis.value?.pendingEnrollments ?? 0,
    subtitle: `${kpis.value?.pendingLeaveRequests ?? 0} leave requests waiting`,
    icon: ClipboardList,
    accent: (kpis.value?.pendingEnrollments ?? 0) > 0 ? 'warning' as const : undefined,
    href: '/enrollment',
  },
])

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { maximumFractionDigits: 0 })
}

function refresh() {
  return load({
    analytics: true,
    activityFeed: true,
    commandCenter: checkCapability('canManageTeachers'),
  })
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
      <Alert v-else-if="partialErrors.length" variant="destructive">
        <AlertDescription>Some widgets failed: {{ partialErrors.join(' · ') }}</AlertDescription>
      </Alert>

      <MetricBand
        title="School overview"
        description="The four numbers that matter most right now"
        :cards="overviewCards"
      />

      <RoleQuickActions variant="admin" />

      <section class="space-y-4" aria-labelledby="ops-title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 id="ops-title" class="text-base font-semibold tracking-tight md:text-lg">
              Daily operations
            </h2>
            <p class="text-sm text-muted-foreground">Attendance and payroll at a glance</p>
          </div>
          <Button variant="outline" size="sm" as-child>
            <RouterLink to="/finance">Open finance</RouterLink>
          </Button>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
          <AttendancePanel :summary="kpis.attendanceSummary" />
          <PayrollPanel :summary="kpis.payrollSummary" />
        </div>
      </section>

      <section class="space-y-4" aria-labelledby="insights-title">
        <div>
          <h2 id="insights-title" class="text-base font-semibold tracking-tight md:text-lg">
            Activity
          </h2>
          <p class="text-sm text-muted-foreground">Recent movement across the school</p>
        </div>
        <div class="grid gap-6 xl:grid-cols-12">
          <div class="xl:col-span-8">
            <ActivityChart :data="activity" />
          </div>
          <div class="xl:col-span-4">
            <ActivityFeed :items="recent" class="min-h-80" />
          </div>
        </div>
      </section>

      <section v-if="commandCenter" class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-base font-semibold tracking-tight md:text-lg">Executive overview</h2>
            <p class="text-sm text-muted-foreground">School health and risk alerts</p>
          </div>
          <Button variant="outline" size="sm" as-child>
            <RouterLink to="/analytics">View analytics</RouterLink>
          </Button>
        </div>
        <CommandCenterSection :data="commandCenter" />
      </section>
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="refresh" />
  </div>
</template>
