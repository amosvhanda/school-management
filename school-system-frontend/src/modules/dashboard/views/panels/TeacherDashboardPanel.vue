<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { ClipboardCheck, FileText, GraduationCap, MessageSquare } from '@lucide/vue'
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
import { TEACHER_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

const AttendancePanel = lazy(() => import('@/components/dashboard/AttendancePanel.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis,
  recent, load,
} = useStaffDashboard()

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: "Today's attendance",
    value: attendanceValue(kpis.value?.attendanceSummary ?? { present: 0, total: 0, absent: 0, late: 0, excused: 0, date: '' }),
    subtitle: attendanceSubtitle(kpis.value?.attendanceSummary ?? { present: 0, total: 0, absent: 0, late: 0, excused: 0, date: '' }),
    icon: ClipboardCheck,
    href: '/academics/attendance',
  },
  {
    title: 'Active students',
    value: kpis.value?.activeStudents ?? 0,
    subtitle: `${kpis.value?.totalClasses ?? 0} classes in school`,
    icon: GraduationCap,
    href: '/students',
  },
  {
    title: 'Exam results',
    value: 'Enter',
    subtitle: 'Marks for your assigned subjects',
    icon: FileText,
    href: '/academics/exams',
  },
  {
    title: 'Messages',
    value: 'Open',
    subtitle: 'Parent and staff threads',
    icon: MessageSquare,
    href: '/communications/threads',
  },
])

function attendanceValue(summary: NonNullable<typeof kpis.value>['attendanceSummary']) {
  if (!summary.total) return '0'
  return `${summary.present}/${summary.total}`
}

function attendanceSubtitle(summary: NonNullable<typeof kpis.value>['attendanceSummary']) {
  return `${summary.absent} absent · ${summary.late} late`
}

onMounted(() => load({ activityFeed: true }))
</script>

<template>
  <div class="mx-auto max-w-350 space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      :role="meta.label"
      :subtitle="meta.subtitle"
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="load({ activityFeed: true })"
    />

    <DashboardSkeleton v-if="loading" />

    <template v-else-if="kpis">
      <Alert v-if="error" variant="destructive"><AlertDescription>{{ error }}</AlertDescription></Alert>

      <MetricBand
        title="Teaching KPIs"
        description="Attendance, learners, exams, and communication"
        :cards="overviewCards"
      />

      <RoleQuickActions variant="teacher" />

      <section class="grid gap-6 lg:grid-cols-2">
        <AttendancePanel :summary="kpis.attendanceSummary" />
        <ActivityFeed :items="recent" class="min-h-80" />
      </section>

      <DashboardModulesGrid
        :groups="TEACHER_DASHBOARD_MODULE_GROUPS"
        title="Your modules"
        description="Teacher tools available for your role"
      />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="load()" />
  </div>
</template>
