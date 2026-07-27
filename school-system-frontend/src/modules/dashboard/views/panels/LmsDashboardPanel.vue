<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  BookOpen,
  GraduationCap,
  Video,
  Users,
} from '@lucide/vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime } from '@/lib/format'
import { queryClient } from '@/lib/query-client'
import { queryKeys } from '@/lib/query-keys'
import { fetchLmsWidgets } from '@/services/dashboard.service'
import type { LmsDashboardWidgets } from '@/types/dashboard'

const { user } = useAuth()

const loading = ref(true)
const error = ref<string | null>(null)
const widgets = ref<LmsDashboardWidgets | null>(null)

const cards = computed<MetricCard[]>(() => {
  const k = widgets.value?.kpis
  return [
    {
      title: 'Total lessons',
      value: k?.total_lessons ?? 0,
      subtitle: 'All LMS sessions',
      icon: BookOpen,
      href: '/teaching?tab=lms',
      capability: 'isStaff',
    },
    {
      title: 'Live sessions',
      value: k?.live_lessons ?? 0,
      subtitle: `${k?.recorded_lessons ?? 0} recorded`,
      icon: Video,
      href: '/teaching?tab=lms',
      capability: 'isStaff',
    },
    {
      title: 'Instructors',
      value: k?.instructors ?? 0,
      subtitle: 'Teachers with LMS sessions',
      icon: Users,
      href: '/people?tab=teachers',
      capability: 'canManageTeachers',
    },
    {
      title: 'Active students',
      value: k?.active_students ?? 0,
      subtitle: 'Learners who can join classes',
      icon: GraduationCap,
      href: '/people?tab=students',
      capability: 'canManageStudents',
    },
  ]
})

async function load() {
  loading.value = true
  error.value = null
  try {
    widgets.value = await queryClient.fetchQuery({
      queryKey: queryKeys.dashboard.lmsWidgets(user.value?.id),
      queryFn: fetchLmsWidgets,
    })
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load LMS dashboard')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      role="LMS"
      subtitle="Online learning overview for your school"
      :loading="loading"
      :last-updated="null"
      @refresh="load"
    >
      <template #actions>
        <Button as-child>
          <RouterLink to="/teaching?tab=lms">Manage LMS sessions</RouterLink>
        </Button>
      </template>
    </DashboardHero>

    <DashboardSkeleton v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="widgets">
      <MetricBand
        title="LMS overview"
        description="Courses, instructors, and learners at a glance"
        :cards="cards"
      />

      <div class="grid gap-6 lg:grid-cols-3">
        <Card class="lg:col-span-2">
          <CardHeader class="border-b border-border/60 px-5 pb-4">
            <CardTitle class="text-base font-semibold tracking-tight">Upcoming sessions</CardTitle>
            <CardDescription>Scheduled online lessons</CardDescription>
          </CardHeader>
          <CardContent class="px-5 pt-4">
            <EmptyState
              v-if="!widgets.upcoming_sessions.length"
              class="py-8"
              title="No upcoming sessions"
              description="Create live or recorded lessons from Manage LMS."
            />
            <ul v-else class="divide-y divide-border/70" aria-label="Upcoming LMS sessions">
              <li
                v-for="session in widgets.upcoming_sessions"
                :key="session.id"
                class="flex flex-wrap items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
              >
                <div class="min-w-0">
                  <p class="font-medium leading-snug">{{ session.title }}</p>
                  <p class="mt-1 text-xs text-muted-foreground">
                    {{ session.teacher_name || 'Unassigned teacher' }}
                    <span v-if="session.class_name"> · {{ session.class_name }}</span>
                  </p>
                  <p class="mt-0.5 text-xs text-muted-foreground">
                    {{ session.scheduled_at ? formatDateTime(session.scheduled_at) : 'Unscheduled' }}
                  </p>
                </div>
                <div class="flex gap-2">
                  <Badge variant="outline" class="capitalize">{{ session.lesson_type }}</Badge>
                  <Badge variant="secondary" class="capitalize">{{ session.status }}</Badge>
                </div>
              </li>
            </ul>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="border-b border-border/60 px-5 pb-4">
            <CardTitle class="text-base font-semibold tracking-tight">Sessions by type</CardTitle>
            <CardDescription>Live, recorded, quizzes, and more</CardDescription>
          </CardHeader>
          <CardContent class="px-5 pt-4">
            <EmptyState
              v-if="!widgets.sessions_by_type.length"
              class="py-8"
              title="No session types yet"
              description="LMS activity will appear here once lessons are created."
            />
            <ul v-else class="space-y-3" aria-label="Sessions by type">
              <li
                v-for="slice in widgets.sessions_by_type"
                :key="slice.label"
                class="flex items-center justify-between gap-3 text-sm"
              >
                <span class="text-muted-foreground">{{ slice.label }}</span>
                <span class="font-semibold tabular-nums">{{ slice.value }}</span>
              </li>
            </ul>
          </CardContent>
        </Card>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader class="border-b border-border/60 px-5 pb-4">
            <CardTitle class="text-base font-semibold tracking-tight">Top instructors</CardTitle>
            <CardDescription>Teachers with the most LMS sessions</CardDescription>
          </CardHeader>
          <CardContent class="px-5 pt-4">
            <EmptyState
              v-if="!widgets.top_instructors.length"
              class="py-8"
              title="No instructors yet"
              description="Assign teachers when creating online lessons."
            />
            <ul v-else class="space-y-3" aria-label="Top instructors">
              <li
                v-for="(instructor, index) in widgets.top_instructors"
                :key="instructor.id"
                class="flex items-center justify-between gap-3"
              >
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium">{{ index + 1 }}. {{ instructor.name }}</p>
                  <p class="truncate text-xs text-muted-foreground">
                    {{ instructor.subject || instructor.email || 'Teacher' }}
                  </p>
                </div>
                <Badge variant="secondary" class="tabular-nums">
                  {{ instructor.lesson_count }} lesson{{ instructor.lesson_count === 1 ? '' : 's' }}
                </Badge>
              </li>
            </ul>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="border-b border-border/60 px-5 pb-4">
            <CardTitle class="text-base font-semibold tracking-tight">Recent sessions</CardTitle>
            <CardDescription>Latest LMS activity</CardDescription>
          </CardHeader>
          <CardContent class="px-5 pt-4">
            <EmptyState
              v-if="!widgets.recent_sessions.length"
              class="py-8"
              title="No sessions yet"
              description="Recent online lessons will list here."
            />
            <ul v-else class="divide-y divide-border/70" aria-label="Recent LMS sessions">
              <li v-for="session in widgets.recent_sessions" :key="session.id" class="py-3 first:pt-0 last:pb-0">
                <p class="text-sm font-medium">{{ session.title }}</p>
                <p class="mt-1 text-xs text-muted-foreground">
                  {{ session.teacher_name || 'Teacher' }}
                  <span v-if="session.lesson_type"> · {{ session.lesson_type }}</span>
                </p>
              </li>
            </ul>
          </CardContent>
        </Card>
      </div>
    </template>
  </div>
</template>
