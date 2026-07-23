<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowRight,
  Bell,
  BookOpen,
  FileCheck,
  FileText,
  GraduationCap,
  Megaphone,
  MessageSquare,
  ShieldAlert,
  TrendingDown,
  UserX,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { useAuth } from '@/composables/useAuth'
import { parentPortalApi } from '@/services/index'
import { PARENT_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

interface PortalDashboard {
  children_count?: number
  unread_notifications?: number
  outstanding_balance?: number
  recent_absences?: number
  open_communications?: number
  pending_consent_forms?: number
  recent_announcements?: number
  recent_results?: number
  open_discipline?: number
}

interface Child {
  id: number
  full_name?: string
  fullName?: string
  student_number?: string
  class?: string
  balance?: number
  currency?: string
}

interface ChildProgressSnippet {
  averagePercent: number | null
  subjectCount: number
  topSubjects: Array<{ subject: string; average_percent: number }>
}

const { user } = useAuth()
const loading = ref(true)
const error = ref<string | null>(null)
const lastUpdated = ref<Date | null>(null)
const dashboard = ref<PortalDashboard | null>(null)
const children = ref<Child[]>([])
const progressByChild = ref<Record<number, ChildProgressSnippet>>({})

const overviewCards = computed<MetricCard[]>(() => {
  const d = dashboard.value
  if (!d) return []
  return [
    {
      title: 'My children',
      value: d.children_count ?? 0,
      subtitle: 'Tap a child below to see progress',
      icon: GraduationCap,
      href: '/portal/children',
    },
    {
      title: 'Recent results',
      value: d.recent_results ?? 0,
      subtitle: 'Published in the last 30 days',
      icon: FileText,
      href: '/portal/children',
    },
    {
      title: 'Outstanding fees',
      value: `$${Number(d.outstanding_balance ?? 0).toLocaleString()}`,
      subtitle: 'Total balance due',
      icon: TrendingDown,
      accent: 'warning',
      href: '/portal/children',
    },
    {
      title: 'Notifications',
      value: d.unread_notifications ?? 0,
      subtitle: 'Unread alerts',
      icon: Bell,
      accent: (d.unread_notifications ?? 0) > 0 ? 'danger' : undefined,
      href: '/portal/hub?tab=notifications',
    },
  ]
})

const activityCards = computed<MetricCard[]>(() => {
  const d = dashboard.value
  if (!d) return []
  const cards: MetricCard[] = [
    {
      title: 'Recent absences',
      value: d.recent_absences ?? 0,
      subtitle: 'Last 30 days',
      icon: UserX,
      accent: (d.recent_absences ?? 0) > 0 ? 'danger' : undefined,
      href: '/portal/children',
    },
    {
      title: 'Open messages',
      value: d.open_communications ?? 0,
      subtitle: 'Active conversations',
      icon: MessageSquare,
      href: '/portal/hub?tab=messages',
    },
    {
      title: 'Consent forms',
      value: d.pending_consent_forms ?? 0,
      subtitle: 'Awaiting your response',
      icon: FileCheck,
      accent: (d.pending_consent_forms ?? 0) > 0 ? 'warning' : undefined,
      href: '/portal/hub?tab=consent',
    },
    {
      title: 'Announcements',
      value: d.recent_announcements ?? 0,
      subtitle: 'Last 30 days',
      icon: Megaphone,
      href: '/portal/hub?tab=announcements',
    },
  ]
  if ((d.open_discipline ?? 0) > 0) {
    cards.push({
      title: 'Discipline notes',
      value: d.open_discipline ?? 0,
      subtitle: 'Recent incidents (90 days)',
      icon: ShieldAlert,
      accent: 'danger',
      href: '/portal/children',
    })
  }
  return cards
})

function childName(child: Child) {
  return child.fullName
    ?? child.full_name
    ?? (child.student_number ? `Student ${child.student_number}` : 'Student')
}

function progressHref(childId: number) {
  return {
    name: 'parent-child-detail' as const,
    params: { id: childId },
    query: { tab: 'progress' },
  }
}

function averageLabel(childId: number) {
  const avg = progressByChild.value[childId]?.averagePercent
  if (avg == null) return 'No marks yet'
  return `${avg.toFixed(1)}% overall`
}

async function loadChildProgress(childRows: Child[]) {
  const entries = await Promise.all(
    childRows.map(async (child) => {
      try {
        const data = await parentPortalApi.progress(child.id) as {
          by_subject?: Array<{ subject?: string; average_percent?: number }>
        }
        const subjects = (data.by_subject ?? [])
          .map((row) => ({
            subject: String(row.subject ?? 'Subject'),
            average_percent: Number(row.average_percent ?? 0),
          }))
          .filter((row) => Number.isFinite(row.average_percent))
          .sort((a, b) => b.average_percent - a.average_percent)

        const averagePercent = subjects.length
          ? subjects.reduce((sum, row) => sum + row.average_percent, 0) / subjects.length
          : null

        return [
          child.id,
          {
            averagePercent,
            subjectCount: subjects.length,
            topSubjects: subjects.slice(0, 3),
          } satisfies ChildProgressSnippet,
        ] as const
      } catch {
        return [
          child.id,
          { averagePercent: null, subjectCount: 0, topSubjects: [] } satisfies ChildProgressSnippet,
        ] as const
      }
    }),
  )

  progressByChild.value = Object.fromEntries(entries)
}

async function load() {
  loading.value = true
  error.value = null
  try {
    dashboard.value = await parentPortalApi.dashboard() as PortalDashboard
    children.value = await parentPortalApi.children() as Child[]
    await loadChildProgress(children.value)
    lastUpdated.value = new Date()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load portal'
  } finally {
    loading.value = false
  }
}

function refresh() {
  return load()
}

onMounted(load)
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      role="Parent"
      subtitle="See how each child is doing at school — marks, attendance, and fees in one place."
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="refresh"
    />

    <PageLoader v-if="loading" label="Loading parent portal…" />
    <ErrorState v-else-if="error" :description="error" @retry="refresh" />

    <template v-else-if="dashboard">
      <section aria-labelledby="parent-progress-heading" class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 id="parent-progress-heading" class="text-base font-semibold tracking-tight md:text-lg">
              Your children’s progress
            </h2>
            <p class="text-sm text-muted-foreground">
              Open a child to see subject averages, recent marks, attendance, and fees.
            </p>
          </div>
          <Button variant="outline" size="sm" as-child>
            <RouterLink to="/portal/children">All children</RouterLink>
          </Button>
        </div>

        <div v-if="children.length" class="grid gap-4 lg:grid-cols-2">
          <Card
            v-for="child in children"
            :key="child.id"
            class="border-border/70"
          >
            <CardHeader class="pb-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 space-y-1">
                  <CardTitle class="truncate text-lg">{{ childName(child) }}</CardTitle>
                  <CardDescription>
                    {{ child.class ?? 'Class not assigned' }}
                    <span v-if="child.student_number"> · {{ child.student_number }}</span>
                  </CardDescription>
                </div>
                <div class="rounded-xl bg-primary/10 px-3 py-2 text-right">
                  <p class="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">Average</p>
                  <p class="text-lg font-semibold tabular-nums text-foreground">
                    {{
                      progressByChild[child.id]?.averagePercent != null
                        ? `${progressByChild[child.id].averagePercent!.toFixed(0)}%`
                        : '—'
                    }}
                  </p>
                </div>
              </div>
            </CardHeader>
            <CardContent class="space-y-4">
              <div
                v-if="progressByChild[child.id]?.topSubjects?.length"
                class="space-y-3"
                :aria-label="`Subject snapshot for ${childName(child)}`"
              >
                <div
                  v-for="subject in progressByChild[child.id].topSubjects"
                  :key="subject.subject"
                  class="space-y-1.5"
                >
                  <div class="flex items-center justify-between gap-2 text-sm">
                    <span class="truncate font-medium">{{ subject.subject }}</span>
                    <span class="tabular-nums text-muted-foreground">{{ subject.average_percent.toFixed(0) }}%</span>
                  </div>
                  <Progress :model-value="Math.min(100, Math.max(0, subject.average_percent))" class="h-2" />
                </div>
              </div>
              <p v-else class="flex items-center gap-2 text-sm text-muted-foreground">
                <BookOpen class="size-4 shrink-0" aria-hidden="true" />
                {{ averageLabel(child.id) }}
              </p>

              <div class="flex flex-wrap gap-2">
                <Button as-child>
                  <RouterLink :to="progressHref(child.id)">
                    View progress
                    <ArrowRight class="ml-2 size-4" aria-hidden="true" />
                  </RouterLink>
                </Button>
                <Button variant="outline" as-child>
                  <RouterLink
                    :to="{ name: 'parent-child-detail', params: { id: child.id }, query: { tab: 'attendance' } }"
                  >
                    Attendance
                  </RouterLink>
                </Button>
                <Button variant="outline" as-child>
                  <RouterLink
                    :to="{ name: 'parent-child-detail', params: { id: child.id }, query: { tab: 'fees' } }"
                  >
                    Fees
                  </RouterLink>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
        <Card v-else class="border-dashed">
          <CardContent class="py-10 text-center text-sm text-muted-foreground">
            No children are linked to your account yet. Contact the school office if this looks wrong.
          </CardContent>
        </Card>
      </section>

      <MetricBand
        title="Family overview"
        description="Quick counts across your linked children"
        :cards="overviewCards"
      />

      <MetricBand
        title="Needs attention"
        description="Absences, messages, consents, and school notices"
        :cards="activityCards"
      />

      <DashboardModulesGrid
        :groups="PARENT_DASHBOARD_MODULE_GROUPS"
        title="More in your portal"
        description="Messages, consent forms, store, and trips"
        skip-permission-filter
      />
    </template>
  </div>
</template>
