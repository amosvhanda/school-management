<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Banknote,
  Briefcase,
  GraduationCap,
  TrendingDown,
  TrendingUp,
  UserRound,
  Users,
  Wallet,
} from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import SchoolDashboardWidgets from '@/components/dashboard/SchoolDashboardWidgets.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { useStaffDashboard } from '@/composables/useStaffDashboard'
import { formatMoney } from '@/lib/finance-constants'
import { lazy } from '@/lib/lazy'
import { getDashboardModuleGroupsForVariant, getRoleDashboardMeta } from '@/lib/role-dashboard'

const AttendancePanel = lazy(() => import('@/components/dashboard/AttendancePanel.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))
const ActivityChart = lazy(() => import('@/components/dashboard/ActivityChart.vue'))
const FeeRevenueChart = lazy(() => import('@/components/dashboard/FeeRevenueChart.vue'))

const { user, checkCapability } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, partialErrors, lastUpdated, kpis,
  recent, schoolWidgets, activity, load,
} = useStaffDashboard()

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'Students',
    value: kpis.value?.totalStudents ?? 0,
    subtitle: `${kpis.value?.activeStudents ?? 0} active · ${kpis.value?.totalClasses ?? 0} classes`,
    icon: GraduationCap,
    trend: kpis.value?.studentsGrowth ?? 0,
    href: '/people?tab=students',
    capability: 'canManageStudents',
  },
  {
    title: 'Teachers',
    value: kpis.value?.totalTeachers ?? 0,
    subtitle: 'Active teaching staff',
    icon: Users,
    trend: kpis.value?.usersChange ?? 0,
    href: '/people?tab=teachers',
    capability: 'canManageTeachers',
  },
  {
    title: 'Staff',
    value: kpis.value?.totalStaff ?? 0,
    subtitle: 'Active employees',
    icon: Briefcase,
    href: '/hr',
    capability: 'canManageTeachers',
  },
  {
    title: 'Parents',
    value: kpis.value?.totalParents ?? 0,
    subtitle: 'Guardian accounts',
    icon: UserRound,
    href: '/people?tab=guardians',
    capability: 'canManageStudents',
  },
])

const financeCards = computed<MetricCard[]>(() => [
  {
    title: 'Collected this month',
    value: formatMoney(kpis.value?.collectedThisMonth ?? 0),
    subtitle: 'Completed fee payments',
    icon: Banknote,
    trend: kpis.value?.paymentsGrowth ?? 0,
    href: '/finance?tab=payments',
    capability: 'canManageFinance',
  },
  {
    title: 'Outstanding fees',
    value: formatMoney(kpis.value?.outstandingFees ?? 0),
    subtitle: 'Unpaid invoice balance',
    icon: Wallet,
    href: '/finance?tab=invoices',
    capability: 'canManageFinance',
  },
  {
    title: 'Income this month',
    value: formatMoney(kpis.value?.incomeThisMonth ?? 0),
    subtitle: 'Ledger income',
    icon: TrendingUp,
    href: '/finance?tab=income',
    capability: 'canManageFinance',
  },
  {
    title: 'Expense this month',
    value: formatMoney(kpis.value?.expenseThisMonth ?? 0),
    subtitle: 'Ledger expense',
    icon: TrendingDown,
    href: '/finance?tab=expense',
    capability: 'canManageFinance',
  },
])

const feeRevenue = computed(() => schoolWidgets.value?.charts?.fee_revenue ?? [])

function refresh() {
  return load({
    analytics: true,
    activityFeed: true,
    schoolWidgets: true,
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
        <AlertDescription>
          Some widgets failed to load: {{ partialErrors.join(' · ') }}
          <Button type="button" variant="link" class="h-auto px-1" @click="refresh">
            Retry
          </Button>
        </AlertDescription>
      </Alert>

      <MetricBand
        title="School overview"
        description="Students, teachers, staff, and parents at a glance"
        :cards="overviewCards"
      />

      <MetricBand
        v-if="checkCapability('canManageFinance')"
        title="Fees & accounts"
        description="Collection, outstanding balances, and ledger totals this month"
        :cards="financeCards"
      />

      <RoleQuickActions variant="admin" />

      <section
        v-if="checkCapability('canManageStudents')"
        class="space-y-4"
        aria-labelledby="attendance-title"
      >
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 id="attendance-title" class="text-base font-semibold tracking-tight md:text-lg">
              Student attendance
            </h2>
            <p class="text-sm text-muted-foreground">
              Today’s present, absent, late, half day, and excused counts
            </p>
          </div>
          <Button variant="outline" size="sm" as-child>
            <RouterLink to="/academics/attendance">Open attendance</RouterLink>
          </Button>
        </div>
        <div class="grid gap-6 xl:grid-cols-12">
          <div class="xl:col-span-5">
            <AttendancePanel :summary="kpis.attendanceSummary" />
          </div>
          <div class="xl:col-span-7">
            <FeeRevenueChart :data="feeRevenue" />
          </div>
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

      <section class="space-y-4" aria-labelledby="school-widgets-title">
        <div>
          <h2 id="school-widgets-title" class="text-base font-semibold tracking-tight md:text-lg">
            School dashboard
          </h2>
          <p class="text-sm text-muted-foreground">
            Charts, notices, leave, calendar, and people highlights
          </p>
        </div>
        <SchoolDashboardWidgets :kpis="kpis" :widgets="schoolWidgets" />
      </section>

      <DashboardModulesGrid
        :groups="getDashboardModuleGroupsForVariant('admin')"
        title="Your modules"
        description="Jump into areas available for your administrator profile"
      />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="refresh" />
  </div>
</template>
