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
  TrendingDown,
  UserX,
} from '@lucide/vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import ParentGreetingCard from '@/components/dashboard/ParentGreetingCard.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { useAuth } from '@/composables/useAuth'
import { parentPortalApi } from '@/services/index'
import { PARENT_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'
import { formatMoney } from '@/lib/finance-constants'
import { formatDate, formatTime } from '@/lib/format'

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
  studentNumber?: string
  class?: string
  balance?: number
  currency?: string
}

interface NoticeRow {
  id?: number
  title?: string
  date?: string
  created_at?: string
  message?: string
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
const selectedChildId = ref<number | null>(null)
const notices = ref<NoticeRow[]>([])

const overviewCards = computed(() => {
  const d = dashboard.value
  if (!d) return []
  return [
    {
      title: 'My children',
      value: d.children_count ?? 0,
      subtitle: 'Linked student profiles',
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
      value: formatMoney(d.outstanding_balance ?? 0),
      subtitle: 'Total balance due',
      icon: TrendingDown,
      accent: (d.outstanding_balance ?? 0) > 0 ? ('warning' as const) : undefined,
      href: '/portal/children',
    },
    {
      title: 'Notifications',
      value: d.unread_notifications ?? 0,
      subtitle: 'Unread alerts',
      icon: Bell,
      accent: (d.unread_notifications ?? 0) > 0 ? ('danger' as const) : undefined,
      href: '/portal/hub?tab=notifications',
    },
    {
      title: 'Recent absences',
      value: d.recent_absences ?? 0,
      subtitle: 'Last 30 days',
      icon: UserX,
      accent: (d.recent_absences ?? 0) > 0 ? ('danger' as const) : undefined,
      href: '/portal/children',
    },
    {
      title: 'Open messages',
      value: d.open_communications ?? 0,
      subtitle: 'Active conversations',
      icon: MessageSquare,
      href: '/portal/hub?tab=messages',
    },
  ]
})

const selectedChild = computed(() =>
  children.value.find((child) => child.id === selectedChildId.value) ?? children.value[0] ?? null,
)

const selectedProgress = computed(() =>
  selectedChild.value ? progressByChild.value[selectedChild.value.id] : undefined,
)

function childNumber(child: Child) {
  return child.studentNumber ?? child.student_number ?? ''
}

const attendanceLabel = computed(() => {
  const absences = dashboard.value?.recent_absences ?? 0
  if (!children.value.length) return '—'
  return absences > 0 ? `${absences} absences` : 'No absences'
})

const quickActions = computed(() => {
  const child = selectedChild.value
  const feesTo = child
    ? { name: 'parent-child-detail' as const, params: { id: child.id }, query: { tab: 'fees' } }
    : '/portal/children'
  const reportTo = child ? progressHref(child.id) : '/portal/children'
  const attendanceTo = child
    ? { name: 'parent-child-detail' as const, params: { id: child.id }, query: { tab: 'attendance' } }
    : '/portal/children'
  return [
    { label: 'Pay Fees', to: feesTo, icon: TrendingDown },
    { label: 'Message Teacher', to: '/portal/hub?tab=messages', icon: MessageSquare },
    { label: 'Report Card', to: reportTo, icon: FileText },
    { label: 'Attendance', to: attendanceTo, icon: UserX },
    { label: 'Consent', to: '/portal/hub?tab=consent', icon: FileCheck },
    { label: 'Announcements', to: '/portal/hub?tab=announcements', icon: Megaphone },
  ]
})

function childName(child: Child) {
  return child.fullName
    ?? child.full_name
    ?? (childNumber(child) ? `Student ${childNumber(child)}` : 'Student')
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
    if (!selectedChildId.value || !children.value.some((child) => child.id === selectedChildId.value)) {
      selectedChildId.value = children.value[0]?.id ?? null
    }
    const noticePayload = await parentPortalApi.announcements().catch(() => [] as NoticeRow[])
    const noticeList: NoticeRow[] = Array.isArray(noticePayload) ? (noticePayload as NoticeRow[]) : []
    notices.value = noticeList.slice(0, 6)
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
  <div class="mx-auto w-full max-w-[1600px] space-y-6 pb-8">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs text-muted-foreground">Dashboard / Parent Dashboard</p>
        <h1 class="mt-1 font-heading text-2xl font-semibold tracking-tight md:text-3xl">
          Parent Dashboard
        </h1>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <span v-if="lastUpdated" class="text-xs text-muted-foreground">
          Updated {{ formatTime(lastUpdated) }}
        </span>
        <Button
          variant="outline"
          size="sm"
          class="h-9 rounded-full"
          :disabled="loading"
          :aria-busy="loading"
          @click="refresh"
        >
          Refresh
        </Button>
      </div>
    </header>

    <DashboardSkeleton v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="refresh" />

    <template v-else-if="dashboard">
      <section v-if="children.length" aria-label="Select a child">
        <p class="mb-2 text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase">Viewing</p>
        <div class="flex flex-wrap gap-2">
          <Button
            v-for="child in children"
            :key="child.id"
            size="sm"
            class="h-9 rounded-full"
            :variant="selectedChild?.id === child.id ? 'default' : 'outline'"
            @click="selectedChildId = child.id"
          >
            {{ childName(child) }}
            <span class="ml-1.5 text-xs opacity-80">{{ child.class ?? '' }}</span>
          </Button>
        </div>
      </section>

      <ParentGreetingCard
        :parent-name="user?.name"
        :child-name="selectedChild ? childName(selectedChild) : undefined"
        :child-class="selectedChild?.class"
        :child-number="selectedChild ? childNumber(selectedChild) : undefined"
        :children-count="dashboard.children_count ?? children.length"
        :attendance-label="attendanceLabel"
        :fee-due="formatMoney(dashboard.outstanding_balance ?? 0)"
        :unread-messages="dashboard.unread_notifications ?? 0"
        :pending-consent="dashboard.pending_consent_forms ?? 0"
      />

      <section aria-labelledby="parent-quick-actions">
        <h2 id="parent-quick-actions" class="mb-3 text-sm font-medium text-muted-foreground">
          Quick actions
        </h2>
        <div class="flex flex-wrap gap-2">
          <Button
            v-for="action in quickActions"
            :key="action.label"
            variant="outline"
            size="sm"
            class="h-9 rounded-full"
            as-child
          >
            <RouterLink :to="action.to">
              <component :is="action.icon" class="mr-1.5 size-3.5" aria-hidden="true" />
              {{ action.label }}
            </RouterLink>
          </Button>
        </div>
      </section>

      <section aria-label="Family overview">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <KpiCard v-for="card in overviewCards" :key="card.title" v-bind="card" />
        </div>
      </section>

      <section aria-labelledby="family-children-heading" class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 id="family-children-heading" class="text-base font-semibold tracking-tight md:text-lg">
              My Children
            </h2>
            <p class="text-sm text-muted-foreground">
              Open a child to see subject averages, attendance, and fees.
            </p>
          </div>
          <Button variant="outline" size="sm" as-child>
            <RouterLink to="/portal/children">Open profiles</RouterLink>
          </Button>
        </div>

        <div v-if="children.length" class="grid gap-4 lg:grid-cols-2">
          <article
            v-for="child in children"
            :key="child.id"
            class="rounded-2xl bg-card p-5 shadow-[var(--shadow-soft)] ring-1 ring-border/80"
            :class="selectedChild?.id === child.id ? 'ring-primary/40' : ''"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h3 class="truncate text-lg font-semibold">{{ childName(child) }}</h3>
                <p class="mt-0.5 text-sm text-muted-foreground">
                  {{ child.class ?? 'Class not assigned' }}
                  <span v-if="childNumber(child)"> · {{ childNumber(child) }}</span>
                </p>
              </div>
              <div class="rounded-2xl bg-primary/10 px-3 py-2 text-right">
                <p class="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">Average</p>
                <p class="text-lg font-semibold tabular-nums">
                  {{
                    progressByChild[child.id]?.averagePercent != null
                      ? `${progressByChild[child.id].averagePercent!.toFixed(0)}%`
                      : '—'
                  }}
                </p>
              </div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <dt class="text-xs text-muted-foreground">Fees due</dt>
                <dd class="mt-1 font-semibold tabular-nums">{{ formatMoney(child.balance ?? 0, child.currency) }}</dd>
              </div>
              <div class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
                <dt class="text-xs text-muted-foreground">Subjects</dt>
                <dd class="mt-1 font-semibold tabular-nums">{{ progressByChild[child.id]?.subjectCount ?? 0 }}</dd>
              </div>
            </dl>

            <div class="mt-4 flex flex-wrap gap-2">
              <Button size="sm" as-child>
                <RouterLink :to="progressHref(child.id)">
                  Details
                  <ArrowRight class="ml-1.5 size-3.5" aria-hidden="true" />
                </RouterLink>
              </Button>
              <Button variant="outline" size="sm" as-child>
                <RouterLink
                  :to="{ name: 'parent-child-detail', params: { id: child.id }, query: { tab: 'attendance' } }"
                >
                  Attendance
                </RouterLink>
              </Button>
              <Button variant="outline" size="sm" as-child>
                <RouterLink
                  :to="{ name: 'parent-child-detail', params: { id: child.id }, query: { tab: 'fees' } }"
                >
                  Fees
                </RouterLink>
              </Button>
            </div>
          </article>
        </div>
        <Card v-else>
          <CardContent class="py-10 text-center text-sm text-muted-foreground">
            No children are linked to your account yet. Contact the school office if this looks wrong.
          </CardContent>
        </Card>
      </section>

      <section class="grid gap-4 xl:grid-cols-12" aria-label="Selected child academics and notices">
        <Card class="xl:col-span-7">
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Subject Vitals</CardTitle>
              <CardDescription>
                {{ selectedChild ? `Latest averages for ${childName(selectedChild)}` : 'Select a child' }}
              </CardDescription>
            </div>
            <Button v-if="selectedChild" variant="outline" size="sm" as-child>
              <RouterLink :to="progressHref(selectedChild.id)">Full Report</RouterLink>
            </Button>
          </CardHeader>
          <CardContent class="space-y-3">
            <p v-if="!selectedProgress?.topSubjects?.length" class="flex items-center gap-2 text-sm text-muted-foreground">
              <BookOpen class="size-4 shrink-0" aria-hidden="true" />
              {{ selectedChild ? averageLabel(selectedChild.id) : 'No child selected.' }}
            </p>
            <div
              v-for="subject in selectedProgress?.topSubjects ?? []"
              :key="subject.subject"
              class="space-y-1.5 rounded-2xl bg-muted/40 px-4 py-3 ring-1 ring-border/60"
            >
              <div class="flex items-center justify-between gap-2 text-sm">
                <span class="truncate font-medium">{{ subject.subject }}</span>
                <span class="tabular-nums font-semibold">{{ subject.average_percent.toFixed(0) }}%</span>
              </div>
              <Progress :model-value="Math.min(100, Math.max(0, subject.average_percent))" class="h-2" />
            </div>
          </CardContent>
        </Card>

        <Card class="xl:col-span-5">
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Communication Hub</CardTitle>
              <CardDescription>School notices for parents.</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/portal/hub?tab=announcements">Inbox</RouterLink>
            </Button>
          </CardHeader>
          <CardContent class="space-y-2">
            <p v-if="!notices.length" class="text-sm text-muted-foreground">No announcements yet.</p>
            <div
              v-for="notice in notices"
              :key="String(notice.id ?? notice.title)"
              class="rounded-xl bg-muted/40 px-3 py-2 ring-1 ring-border/60"
            >
              <p class="truncate font-medium">{{ notice.title ?? 'Notice' }}</p>
              <p class="text-xs text-muted-foreground">
                {{ formatDate(notice.date ?? notice.created_at) }}
              </p>
            </div>
            <div class="flex flex-wrap gap-2 pt-2">
              <Badge v-if="(dashboard.pending_consent_forms ?? 0) > 0" variant="outline">
                {{ dashboard.pending_consent_forms }} consent pending
              </Badge>
              <Badge v-if="(dashboard.open_discipline ?? 0) > 0" variant="outline">
                {{ dashboard.open_discipline }} discipline notes
              </Badge>
            </div>
          </CardContent>
        </Card>
      </section>

      <DashboardModulesGrid
        :groups="PARENT_DASHBOARD_MODULE_GROUPS"
        title="More in your portal"
        description="Messages, consent forms, store, and trips"
        skip-permission-filter
      />
    </template>
  </div>
</template>
