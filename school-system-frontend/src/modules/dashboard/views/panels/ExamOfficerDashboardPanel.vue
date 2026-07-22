<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { BookOpen, CheckCircle2, FileText, GraduationCap, Upload } from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import RoleQuickActions from '@/components/dashboard/RoleQuickActions.vue'
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
import { academicsApi } from '@/services/api.service'

const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

interface ExamRow {
  id: number
  is_published?: boolean
  exam_results_count?: number
  results_approved_at?: string | null
  exam_date?: string
}

const { user } = useAuth()
const meta = getRoleDashboardMeta(user.value?.role)

const {
  loading, error, lastUpdated, kpis, recent, load,
} = useStaffDashboard()

const examStats = ref({ total: 0, published: 0, pending: 0, upcoming: 0 })

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'Total exams',
    value: String(examStats.value.total),
    subtitle: 'Scheduled in system',
    icon: FileText,
    href: '/academics/exams',
  },
  {
    title: 'Upcoming',
    value: String(examStats.value.upcoming),
    subtitle: 'On or after today',
    icon: BookOpen,
    accent: 'warning' as const,
    href: '/academics/exams',
  },
  {
    title: 'Pending approval',
    value: String(examStats.value.pending),
    subtitle: 'Results awaiting sign-off',
    icon: CheckCircle2,
    href: '/academics/exams',
  },
  {
    title: 'Published',
    value: String(examStats.value.published),
    subtitle: 'Visible to parents',
    icon: Upload,
    accent: 'success' as const,
    href: '/academics/exams',
  },
])

async function loadExamStats() {
  try {
    const exams = await academicsApi.exams.list({ all: true }) as ExamRow[]
    const today = new Date().toISOString().slice(0, 10)
    examStats.value = {
      total: exams.length,
      published: exams.filter((e) => e.is_published).length,
      pending: exams.filter((e) => (e.exam_results_count ?? 0) > 0 && !e.results_approved_at).length,
      upcoming: exams.filter((e) => String(e.exam_date ?? '').slice(0, 10) >= today).length,
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
  <div class="mx-auto w-full max-w-[1600px] space-y-8 pb-8">
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
      <Alert v-if="error" variant="destructive">
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <MetricBand
        title="Examination overview"
        description="Schedule, approvals, and publishing status"
        :cards="overviewCards"
      />

      <RoleQuickActions variant="examination_officer" />

      <section class="grid gap-6 lg:grid-cols-2" aria-label="Examination workspace">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">School context</CardTitle>
            <CardDescription>Students and classes under assessment</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-xl border border-border/60 bg-muted/20 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Students</p>
                <p class="mt-2 flex items-center gap-2 text-2xl font-semibold">
                  <GraduationCap class="size-5 text-muted-foreground" aria-hidden="true" />
                  {{ kpis.activeStudents }}
                </p>
              </div>
              <div class="rounded-xl border border-border/60 bg-muted/20 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Classes</p>
                <p class="mt-2 text-2xl font-semibold">{{ kpis.totalClasses }}</p>
              </div>
            </div>
            <Button variant="outline" as-child>
              <RouterLink to="/students">View students</RouterLink>
            </Button>
          </CardContent>
        </Card>

        <ActivityFeed :items="recent" class="min-h-72" />
      </section>
    </template>

    <ErrorState v-else-if="error" :description="error" @retry="refreshAll" />
  </div>
</template>
