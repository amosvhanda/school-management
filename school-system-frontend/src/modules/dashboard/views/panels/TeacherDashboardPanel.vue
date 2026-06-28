<script setup lang="ts">
import { onMounted } from 'vue'
import { BookOpen, ClipboardCheck, GraduationCap, GitBranch } from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import AttendancePanel from '@/components/dashboard/AttendancePanel.vue'
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
  recent, pendingWorkflows, load,
} = useStaffDashboard()

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
  <div class="mx-auto max-w-[1400px] space-y-8 pb-8">
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

      <section aria-labelledby="teacher-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="teacher-kpis" class="sr-only">Teaching KPIs</h2>
        <KpiCard
          title="Today's attendance"
          :value="attendanceValue(kpis.attendanceSummary)"
          :subtitle="attendanceSubtitle(kpis.attendanceSummary)"
          :icon="ClipboardCheck"
          href="/academics/attendance"
        />
        <KpiCard
          title="Classes"
          :value="kpis.totalClasses"
          :subtitle="`${kpis.activeStudents} active students`"
          :icon="BookOpen"
          href="/academics/setup"
        />
        <KpiCard
          title="Students"
          :value="kpis.activeStudents"
          :subtitle="`${kpis.totalStudents} enrolled`"
          :icon="GraduationCap"
          href="/students"
        />
        <KpiCard
          title="Pending workflows"
          :value="String(pendingWorkflows)"
          subtitle="Items awaiting action"
          :icon="GitBranch"
          href="/workflows"
        />
      </section>

      <RoleQuickActions variant="teacher" />

      <section class="grid gap-6 lg:grid-cols-2">
        <AttendancePanel :summary="kpis.attendanceSummary" />
        <ActivityFeed :items="recent" class="min-h-[320px]" />
      </section>

      <DashboardModulesGrid
        :groups="STAFF_DASHBOARD_MODULE_GROUPS"
        title="Your modules"
        description="All teaching and school areas available to you"
      />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="load()" />
  </div>
</template>
