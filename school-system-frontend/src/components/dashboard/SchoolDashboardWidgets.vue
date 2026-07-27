<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ClipboardList,
  Megaphone,
  UserPlus,
  Users,
} from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import AdmissionsDonutChart from '@/components/dashboard/AdmissionsDonutChart.vue'
import DashboardMonthCalendar from '@/components/dashboard/DashboardMonthCalendar.vue'
import IncomeExpenseChart from '@/components/dashboard/IncomeExpenseChart.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { formatDate, formatDateTime } from '@/lib/format'
import type { DashboardKpis, SchoolDashboardWidgets } from '@/types/dashboard'

const props = defineProps<{
  kpis: DashboardKpis
  widgets: SchoolDashboardWidgets | null
}>()

const notices = computed(() => props.widgets?.notices ?? [])
const leaveRequests = computed(() => props.widgets?.leave_requests ?? [])
const events = computed(() => props.widgets?.upcoming_events ?? [])
const topTeachers = computed(() => props.widgets?.top_teachers ?? [])
const topStudents = computed(() => props.widgets?.top_students ?? [])
const newAdmissions = computed(() => props.widgets?.new_admissions ?? [])
const charts = computed(() => props.widgets?.charts ?? null)

const incomeExpense = computed(() => charts.value?.income_expense ?? [])
const admissionsByClass = computed(() => charts.value?.admissions_by_class ?? [])
const calendarEvents = computed(() => charts.value?.calendar_events ?? [])

const userSegments = computed(() => {
  const students = props.kpis.totalStudents ?? 0
  const teachers = props.kpis.totalTeachers ?? 0
  const staff = props.kpis.totalStaff ?? 0
  const parents = props.kpis.totalParents ?? 0
  const total = Math.max(students + teachers + staff + parents, 1)
  return [
    { label: 'Students', value: students, pct: Math.round((students / total) * 100), class: 'bg-primary' },
    { label: 'Teachers', value: teachers, pct: Math.round((teachers / total) * 100), class: 'bg-chart-2' },
    { label: 'Staff', value: staff, pct: Math.round((staff / total) * 100), class: 'bg-chart-3' },
    { label: 'Parents', value: parents, pct: Math.round((parents / total) * 100), class: 'bg-chart-4' },
  ]
})

function personInitials(name: string) {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('')
}
</script>

<template>
  <div class="space-y-6">
    <!-- Charts: income/expense + admissions (fee revenue sits beside attendance on school dash) -->
    <div class="grid gap-6 lg:grid-cols-2">
      <IncomeExpenseChart :data="incomeExpense" />
      <AdmissionsDonutChart :data="admissionsByClass" />
    </div>

    <!-- User overview + calendar -->
    <div class="grid gap-6 lg:grid-cols-2">
      <Card class="h-full">
        <CardHeader class="border-b border-border/60 px-5 pb-4">
          <CardTitle class="text-base font-semibold tracking-tight">User overview</CardTitle>
          <CardDescription>{{ kpis.totalUsers }} accounts on this school</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4 px-5 pt-5">
          <div
            class="flex h-3 overflow-hidden rounded-full bg-muted"
            role="img"
            :aria-label="userSegments.map((s) => `${s.label} ${s.value}`).join(', ')"
          >
            <div
              v-for="seg in userSegments.filter((s) => s.value > 0)"
              :key="seg.label"
              :class="seg.class"
              :style="{ width: `${seg.pct}%` }"
              :title="`${seg.label}: ${seg.value}`"
            />
          </div>
          <ul class="grid gap-3 sm:grid-cols-2" aria-label="User breakdown">
            <li
              v-for="seg in userSegments"
              :key="seg.label"
              class="rounded-lg border border-border/70 bg-muted/20 p-3"
            >
              <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2 text-xs text-muted-foreground">
                  <span :class="['size-2 rounded-full', seg.class]" aria-hidden="true" />
                  {{ seg.label }}
                </span>
                <Badge variant="secondary" class="tabular-nums">{{ seg.pct }}%</Badge>
              </div>
              <p class="mt-1.5 text-xl font-semibold tabular-nums tracking-tight">{{ seg.value }}</p>
            </li>
          </ul>
        </CardContent>
      </Card>

      <DashboardMonthCalendar :events="calendarEvents" />
    </div>

    <!-- Notices, leaves, events -->
    <div class="grid gap-6 lg:grid-cols-3">
      <Card class="h-full">
        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pb-4 space-y-0">
          <div class="space-y-1">
            <CardTitle class="text-base font-semibold tracking-tight">Notice board</CardTitle>
            <CardDescription>Latest school announcements</CardDescription>
          </div>
          <Button variant="ghost" size="sm" class="shrink-0" as-child>
            <RouterLink to="/communications/announcements" aria-label="View all notices">
              <Megaphone class="size-4" aria-hidden="true" />
            </RouterLink>
          </Button>
        </CardHeader>
        <CardContent class="px-5 pt-4">
          <EmptyState
            v-if="!notices.length"
            class="py-8"
            title="No notices yet"
            description="Published announcements will show here."
          />
          <ul v-else class="divide-y divide-border/70" aria-label="Notice board">
            <li v-for="notice in notices" :key="notice.id" class="py-3 first:pt-0 last:pb-0">
              <p class="text-sm font-medium leading-snug">{{ notice.title }}</p>
              <p class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ notice.message }}</p>
              <p class="mt-1.5 text-[11px] text-muted-foreground/80">
                {{ notice.author }}
                <span v-if="notice.date"> · {{ formatDate(notice.date) }}</span>
              </p>
            </li>
          </ul>
        </CardContent>
      </Card>

      <Card class="h-full">
        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pb-4 space-y-0">
          <div class="space-y-1">
            <CardTitle class="text-base font-semibold tracking-tight">Leave requests</CardTitle>
            <CardDescription>Pending approval</CardDescription>
          </div>
          <Button variant="ghost" size="sm" class="shrink-0" as-child>
            <RouterLink to="/hr/leave" aria-label="View leave requests">
              <ClipboardList class="size-4" aria-hidden="true" />
            </RouterLink>
          </Button>
        </CardHeader>
        <CardContent class="px-5 pt-4">
          <EmptyState
            v-if="!leaveRequests.length"
            class="py-8"
            title="No pending leave"
            description="Staff leave requests awaiting review will appear here."
          />
          <ul v-else class="divide-y divide-border/70" aria-label="Pending leave requests">
            <li v-for="leave in leaveRequests" :key="leave.id" class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium">{{ leave.teacher_name }}</p>
                <p class="mt-0.5 text-xs capitalize text-muted-foreground">
                  {{ (leave.type || 'leave').replaceAll('_', ' ') }}
                  <span v-if="leave.days"> · {{ leave.days }} day{{ leave.days === 1 ? '' : 's' }}</span>
                </p>
                <p class="mt-0.5 text-[11px] text-muted-foreground/80">
                  <span v-if="leave.applied_on">Applied {{ formatDate(leave.applied_on) }}</span>
                  <span v-if="leave.start_date">
                    <span v-if="leave.applied_on"> · </span>
                    {{ formatDate(leave.start_date) }}
                    <span v-if="leave.end_date"> – {{ formatDate(leave.end_date) }}</span>
                  </span>
                </p>
              </div>
              <Badge variant="secondary" class="shrink-0">Pending</Badge>
            </li>
          </ul>
        </CardContent>
      </Card>

      <Card class="h-full">
        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pb-4 space-y-0">
          <div class="space-y-1">
            <CardTitle class="text-base font-semibold tracking-tight">Upcoming events</CardTitle>
            <CardDescription>Next on the school calendar</CardDescription>
          </div>
          <Button variant="ghost" size="sm" class="shrink-0" as-child>
            <RouterLink to="/operations/events" aria-label="View events">
              View all
            </RouterLink>
          </Button>
        </CardHeader>
        <CardContent class="px-5 pt-4">
          <EmptyState
            v-if="!events.length"
            class="py-8"
            title="No upcoming events"
            description="Scheduled events will show on this list."
          />
          <ul v-else class="divide-y divide-border/70" aria-label="Upcoming events">
            <li v-for="event in events" :key="event.id" class="py-3 first:pt-0 last:pb-0">
              <p class="text-sm font-medium leading-snug">{{ event.title }}</p>
              <p class="mt-1 text-xs text-muted-foreground">
                {{ formatDateTime(event.starts_at) }}
                <span v-if="event.location"> · {{ event.location }}</span>
              </p>
              <Badge v-if="event.type" variant="outline" class="mt-2 capitalize">
                {{ event.type.replaceAll('_', ' ') }}
              </Badge>
            </li>
          </ul>
        </CardContent>
      </Card>
    </div>

    <!-- People leaderboards -->
    <div class="grid gap-6 lg:grid-cols-3">
      <Card class="h-full">
        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pb-4 space-y-0">
          <div class="space-y-1">
            <CardTitle class="text-base font-semibold tracking-tight">Teachers</CardTitle>
            <CardDescription>Active teaching staff</CardDescription>
          </div>
          <Button variant="ghost" size="sm" class="shrink-0" as-child>
            <RouterLink to="/people?tab=teachers" aria-label="View teachers">
              <Users class="size-4" aria-hidden="true" />
            </RouterLink>
          </Button>
        </CardHeader>
        <CardContent class="px-5 pt-4">
          <EmptyState
            v-if="!topTeachers.length"
            class="py-8"
            title="No teachers yet"
            description="Add teaching staff to populate this list."
          />
          <ul v-else class="space-y-3" aria-label="Teachers">
            <li
              v-for="teacher in topTeachers"
              :key="teacher.id"
            >
              <RouterLink
                :to="`/teachers/${teacher.id}`"
                class="flex items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              >
                <span
                  class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                  aria-hidden="true"
                >
                  {{ personInitials(teacher.name) }}
                </span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-medium">{{ teacher.name }}</p>
                  <p class="truncate text-xs text-muted-foreground">
                    {{ teacher.subject || teacher.department || teacher.email || 'Teacher' }}
                  </p>
                </div>
              </RouterLink>
            </li>
          </ul>
        </CardContent>
      </Card>

      <Card class="h-full">
        <CardHeader class="flex flex-row items-start justify-between gap-3 border-b border-border/60 px-5 pb-4 space-y-0">
          <div class="space-y-1">
            <CardTitle class="text-base font-semibold tracking-tight">Recent admissions</CardTitle>
            <CardDescription>
              {{ kpis.newAdmissionsThisMonth ?? newAdmissions.length }} this month
            </CardDescription>
          </div>
          <Button variant="ghost" size="sm" class="shrink-0" as-child>
            <RouterLink to="/people?tab=students" aria-label="View students">
              <UserPlus class="size-4" aria-hidden="true" />
            </RouterLink>
          </Button>
        </CardHeader>
        <CardContent class="px-5 pt-4">
          <EmptyState
            v-if="!newAdmissions.length"
            class="py-8"
            title="No new admissions"
            description="Students enrolled this month will appear here."
          />
          <ul v-else class="space-y-3" aria-label="New admissions">
            <li
              v-for="student in newAdmissions"
              :key="student.id"
            >
              <RouterLink
                :to="`/students/${student.id}`"
                class="flex items-center justify-between gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-chart-2/15 text-xs font-semibold text-chart-2"
                    aria-hidden="true"
                  >
                    {{ personInitials(student.name) }}
                  </span>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium">{{ student.name }}</p>
                    <p v-if="student.class_name" class="truncate text-xs text-muted-foreground">
                      {{ student.class_name }}
                    </p>
                  </div>
                </div>
                <span v-if="student.joined_on" class="shrink-0 text-[11px] text-muted-foreground">
                  {{ formatDate(student.joined_on) }}
                </span>
              </RouterLink>
            </li>
          </ul>
        </CardContent>
      </Card>

      <Card class="h-full">
        <CardHeader class="border-b border-border/60 px-5 pb-4">
          <CardTitle class="text-base font-semibold tracking-tight">Top students</CardTitle>
          <CardDescription>Highest exam averages (when results exist)</CardDescription>
        </CardHeader>
        <CardContent class="px-5 pt-4">
          <EmptyState
            v-if="!topStudents.length"
            class="py-8"
            title="No ranked students yet"
            description="Students appear here once exam results with percentages are recorded."
          />
          <ul v-else class="space-y-3" aria-label="Top students">
            <li
              v-for="(student, index) in topStudents"
              :key="student.id"
            >
              <RouterLink
                :to="`/students/${student.id}`"
                class="flex items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              >
                <span
                  class="relative flex size-10 shrink-0 items-center justify-center"
                  aria-hidden="true"
                >
                  <svg viewBox="0 0 36 36" class="size-10 -rotate-90" role="presentation">
                    <circle
                      cx="18"
                      cy="18"
                      r="15.5"
                      fill="none"
                      class="stroke-muted"
                      stroke-width="3"
                    />
                    <circle
                      cx="18"
                      cy="18"
                      r="15.5"
                      fill="none"
                      class="stroke-chart-2"
                      stroke-width="3"
                      stroke-linecap="round"
                      :stroke-dasharray="`${Math.min(100, Math.max(0, Number(student.marks) || 0)) * 0.973}, 100`"
                    />
                  </svg>
                  <span class="absolute text-[10px] font-semibold tabular-nums">{{ index + 1 }}</span>
                </span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-medium">{{ student.name }}</p>
                  <p class="truncate text-xs text-muted-foreground">
                    {{ student.class_name || 'Student' }}
                  </p>
                </div>
                <Badge v-if="student.marks != null" variant="secondary" class="tabular-nums">
                  {{ student.marks }}%
                </Badge>
              </RouterLink>
            </li>
          </ul>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
