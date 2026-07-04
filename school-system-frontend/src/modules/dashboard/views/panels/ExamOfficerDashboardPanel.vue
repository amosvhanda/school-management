<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { BookOpen, CheckCircle2, FileText, Upload } from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { useAuth } from '@/composables/useAuth'
import { useStaffDashboard } from '@/composables/useStaffDashboard'
import { lazy } from '@/lib/lazy'
import { getRoleDashboardMeta } from '@/lib/role-dashboard'
import { STAFF_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'
import { academicsApi } from '@/services/api.service'

const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

interface ExamRow {
  id: number
  is_published?: boolean
  exam_results_count?: number
  results_approved_at?: string | null
}

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis, recent, load,
} = useStaffDashboard()

const examStats = ref({ total: 0, published: 0, pending: 0, upcoming: 0 })

const overviewCards = computed<MetricCard[]>(() => [
  { title: 'Total exams', value: String(examStats.value.total), subtitle: 'Scheduled in system', icon: FileText, href: '/academics/exams' },
  { title: 'Upcoming', value: String(examStats.value.upcoming), subtitle: 'On or after today', icon: BookOpen, accent: 'warning' as const, href: '/academics/exams' },
  { title: 'Pending approval', value: String(examStats.value.pending), subtitle: 'Results awaiting sign-off', icon: CheckCircle2, href: '/academics/exams' },
  { title: 'Published', value: String(examStats.value.published), subtitle: 'Visible to parents', icon: Upload, accent: 'success' as const, href: '/academics/exams' },
])

async function loadExamStats() {
  try {
    const exams = await academicsApi.exams.list() as ExamRow[]
    const today = new Date().toISOString().slice(0, 10)
    examStats.value = {
      total: exams.length,
      published: exams.filter((e) => e.is_published).length,
      pending: exams.filter((e) => (e.exam_results_count ?? 0) > 0 && !e.results_approved_at).length,
      upcoming: exams.filter((e) => String((e as { exam_date?: string }).exam_date ?? '').slice(0, 10) >= today).length,
    }
  } catch {
    examStats.value = { total: 0, published: 0, pending: 0, upcoming: 0 }
  }
}

async function refreshAll() {
  await Promise.all([
    load({ activityFeed: true }),
    loadExamStats(),
  ])
}

onMounted(refreshAll)
</script>

<template>
  <div class="mx-auto max-w-350 space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      :role="meta.label"
      :subtitle="meta.subtitle"
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="refreshAll"
    />

    <DashboardSkeleton v-if="loading" />

    <template v-else-if="kpis">
      <Alert v-if="error" variant="destructive"><AlertDescription>{{ error }}</AlertDescription></Alert>

      <MetricBand title="Examination KPIs" description="State of exams and result publishing" :cards="overviewCards" />

      <RoleQuickActions variant="examination_officer" />

      <div class="grid gap-6 lg:grid-cols-3">
        <Card class="lg:col-span-1">
          <CardHeader>
            <CardTitle class="text-base">Examination centre</CardTitle>
            <CardDescription>Schedule exams, enter marks, approve and publish results.</CardDescription>
          </CardHeader>
          <CardContent class="flex flex-col gap-2">
            <Button as-child><RouterLink to="/academics/exams">Manage examinations</RouterLink></Button>
            <Button variant="outline" as-child><RouterLink to="/academics/grades">Open gradebook</RouterLink></Button>
            <Button variant="outline" as-child><RouterLink to="/academics/tests">Class tests</RouterLink></Button>
          </CardContent>
        </Card>

        <Card class="lg:col-span-1">
          <CardHeader>
            <CardTitle class="text-base">School context</CardTitle>
            <CardDescription>Students and classes under assessment</CardDescription>
          </CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p><span class="font-medium">{{ kpis.activeStudents }}</span> active students</p>
            <p><span class="font-medium">{{ kpis.totalClasses }}</span> classes</p>
            <Button variant="link" class="h-auto p-0" as-child>
              <RouterLink to="/students">View students</RouterLink>
            </Button>
          </CardContent>
        </Card>

        <div class="lg:col-span-1">
          <ActivityFeed :items="recent" class="min-h-60" />
        </div>
      </div>

      <DashboardModulesGrid
        :groups="STAFF_DASHBOARD_MODULE_GROUPS"
        title="Your modules"
        description="Examinations, academics, and related areas"
      />
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="refreshAll" />
  </div>
</template>
