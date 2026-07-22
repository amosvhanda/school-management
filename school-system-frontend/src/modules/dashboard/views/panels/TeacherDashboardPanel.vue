<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { BookOpen, ClipboardCheck, GraduationCap, MessageSquare } from '@lucide/vue'
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

const AttendancePanel = lazy(() => import('@/components/dashboard/AttendancePanel.vue'))
const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis,
  recent, load,
} = useStaffDashboard()

const overviewCards = computed<MetricCard[]>(() => {
  const summary = kpis.value?.attendanceSummary ?? {
    present: 0,
    total: 0,
    absent: 0,
    late: 0,
    excused: 0,
    date: '',
  }

  return [
    {
      title: "Today's attendance",
      value: summary.total ? `${summary.present}/${summary.total}` : '0',
      subtitle: `${summary.absent} absent · ${summary.late} late`,
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
      title: 'Classes',
      value: kpis.value?.totalClasses ?? 0,
      subtitle: 'School-wide class groups',
      icon: BookOpen,
      href: '/academics/my-timetable',
    },
    {
      title: 'Parents linked',
      value: kpis.value?.totalParents ?? 0,
      subtitle: 'Open messages for follow-up',
      icon: MessageSquare,
      href: '/communications/threads',
    },
  ]
})

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
        title="Teaching overview"
        description="Attendance and learners for today"
        :cards="overviewCards"
      />

      <RoleQuickActions variant="teacher" />

      <section class="grid gap-6 lg:grid-cols-2" aria-label="Teaching workspace">
        <AttendancePanel :summary="kpis.attendanceSummary" />
        <ActivityFeed :items="recent" class="min-h-80" />
      </section>
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="refresh" />
  </div>
</template>
