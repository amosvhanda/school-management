<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
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
import { canShowDashboardItem } from '@/lib/dashboard-access'
import { lazy } from '@/lib/lazy'
import { getDashboardModuleGroupsForVariant, getRoleDashboardMeta } from '@/lib/role-dashboard'
import { academicsApi } from '@/services/api.service'
import type { RecentActivityItem } from '@/types/dashboard'

const ActivityFeed = lazy(() => import('@/components/dashboard/ActivityFeed.vue'))

interface ExamRow {
  id: number
  is_published?: boolean
  exam_results_count?: number
  results_approved_at?: string | null
  exam_date?: string
  name?: string
}

const { user } = useAuth()
const router = useRouter()
const meta = getRoleDashboardMeta(user.value?.role)

const canOpenExams = computed(() =>
  canShowDashboardItem(
    user.value,
    { href: '/academics/exams', capability: ['canManageExaminations', 'canEnterExamResults'] },
    router,
  ),
)

const canOpenGradebook = computed(() =>
  canShowDashboardItem(
    user.value,
    { href: '/academics/grades', capability: ['canManageExaminations', 'canEnterExamResults'] },
    router,
  ),
)

const canOpenTests = computed(() =>
  canShowDashboardItem(
    user.value,
    { href: '/academics/tests', capability: ['canManageExaminations', 'canEnterExamResults'] },
    router,
  ),
)

const hasExamWorkspace = computed(
  () => canOpenExams.value || canOpenGradebook.value || canOpenTests.value,
)

const loading = ref(true)
const error = ref<string | null>(null)
const lastUpdated = ref<Date | null>(null)
const examStats = ref({ total: 0, published: 0, pending: 0, upcoming: 0 })
const recent = ref<RecentActivityItem[]>([])

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'Total exams',
    value: String(examStats.value.total),
    subtitle: 'Scheduled in system',
    icon: FileText,
    href: '/academics/exams',
    capability: 'canManageExaminations',
  },
  {
    title: 'Upcoming',
    value: String(examStats.value.upcoming),
    subtitle: 'On or after today',
    icon: BookOpen,
    accent: 'warning' as const,
    href: '/academics/exams',
    capability: 'canManageExaminations',
  },
  {
    title: 'Pending approval',
    value: String(examStats.value.pending),
    subtitle: 'Results awaiting sign-off',
    icon: CheckCircle2,
    href: '/academics/exams',
    capability: 'canManageExaminations',
  },
  {
    title: 'Published',
    value: String(examStats.value.published),
    subtitle: 'Visible to parents',
    icon: Upload,
    accent: 'success' as const,
    href: '/academics/exams',
    capability: 'canManageExaminations',
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
    recent.value = exams.slice(0, 8).map((exam) => ({
      id: exam.id,
      type: 'exam',
      action: exam.is_published ? 'published' : 'scheduled',
      user: 'Examinations',
      description: exam.name
        ? `${exam.name}${exam.exam_date ? ` · ${String(exam.exam_date).slice(0, 10)}` : ''}`
        : (exam.exam_date ? `Exam on ${String(exam.exam_date).slice(0, 10)}` : 'Exam update'),
      timestamp: exam.exam_date ? String(exam.exam_date) : new Date().toISOString(),
      status: exam.is_published ? 'published' : 'pending',
    }))
  } catch (err) {
    examStats.value = { total: 0, published: 0, pending: 0, upcoming: 0 }
    recent.value = []
    error.value = err instanceof Error ? err.message : 'Could not load examination data.'
  }
}

async function refreshAll() {
  loading.value = true
  error.value = null
  try {
    await loadExamStats()
    lastUpdated.value = new Date()
  } finally {
    loading.value = false
  }
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

    <template v-else>
      <Alert v-if="error" variant="destructive">
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <MetricBand
        title="Examination overview"
        description="Schedule, approvals, and publishing status"
        :cards="overviewCards"
      />

      <RoleQuickActions variant="examination_officer" />

      <DashboardModulesGrid
        :groups="getDashboardModuleGroupsForVariant('examination_officer')"
        title="Examination modules"
        description="Only exam tools available for your profile"
      />

      <section class="grid gap-6 lg:grid-cols-2" aria-label="Examination workspace">
        <Card v-if="hasExamWorkspace">
          <CardHeader>
            <CardTitle class="text-base">Exam workspace</CardTitle>
            <CardDescription>Open the tools you use every day</CardDescription>
          </CardHeader>
          <CardContent class="flex flex-wrap gap-2">
            <Button v-if="canOpenExams" as-child>
              <RouterLink to="/academics/exams">Manage exams</RouterLink>
            </Button>
            <Button v-if="canOpenGradebook" variant="outline" as-child>
              <RouterLink to="/academics/grades">Open gradebook</RouterLink>
            </Button>
            <Button v-if="canOpenTests" variant="outline" as-child>
              <RouterLink to="/academics/tests">Class tests</RouterLink>
            </Button>
          </CardContent>
        </Card>

        <ActivityFeed :items="recent" class="min-h-72" />
      </section>
    </template>

    <ErrorState v-if="!loading && error && examStats.total === 0" :description="error" @retry="refreshAll" />
  </div>
</template>
