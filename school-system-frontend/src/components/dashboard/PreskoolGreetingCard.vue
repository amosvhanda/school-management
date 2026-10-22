<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Banknote,
  CalendarDays,
  GraduationCap,
  Receipt,
  TrendingUp,
  UserPlus,
} from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/format'
import { formatMoney as formatCurrency } from '@/lib/finance-constants'
import type { AttendanceSummary } from '@/types/dashboard'

const props = defineProps<{
  name?: string
  schoolName?: string
  academicYear?: string | null
  attendance?: AttendanceSummary | null
  newAdmissions?: number
  feesToday?: number
  upcomingEvents?: number
  studentsGrowth?: number
  paymentsGrowth?: number
}>()

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good Morning'
  if (hour < 17) return 'Good Afternoon'
  return 'Good Evening'
})

const firstName = computed(() => props.name?.split(' ')[0] ?? 'there')

const todayLine = computed(() => {
  const now = new Date()
  const weekday = now.toLocaleDateString('en-GB', {
    weekday: 'short',
    timeZone: 'Africa/Harare',
  })
  const date = formatDate(now)
  const year = props.academicYear ? ` · AY ${props.academicYear}` : ''
  return `${props.schoolName ?? 'Your school'} · ${weekday}, ${date}${year}`
})

const attendanceRate = computed(() => {
  const total = Number(props.attendance?.total ?? 0)
  const present = Number(props.attendance?.present ?? 0)
  if (total <= 0) return null
  return Math.round((present / total) * 1000) / 10
})

function trendLabel(value?: number) {
  if (value == null || Number.isNaN(value) || value === 0) return null
  const sign = value > 0 ? '+' : ''
  return `${sign}${value}%`
}
</script>

<template>
  <section
    class="overflow-hidden rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80 sm:p-5"
    aria-labelledby="dashboard-greeting"
  >
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1.4fr)] lg:items-center">
      <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
        <div
          class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground"
          aria-hidden="true"
        >
          <GraduationCap class="size-7" />
        </div>
        <div class="min-w-0 space-y-3">
          <div>
            <h2 id="dashboard-greeting" class="text-xl font-semibold tracking-tight sm:text-2xl">
              {{ greeting }}, {{ firstName }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">{{ todayLine }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button size="sm" class="h-9 rounded-full px-4" as-child>
              <RouterLink to="/people?tab=students&create=1">
                <UserPlus class="mr-1.5 size-3.5" aria-hidden="true" />
                New Admission
              </RouterLink>
            </Button>
            <Button size="sm" variant="secondary" class="h-9 rounded-full px-4" as-child>
              <RouterLink to="/finance?tab=payments&create=1">
                <Banknote class="mr-1.5 size-3.5" aria-hidden="true" />
                Collect Fees
              </RouterLink>
            </Button>
            <Button size="sm" variant="outline" class="h-9 rounded-full px-4" as-child>
              <RouterLink to="/reports">
                <Receipt class="mr-1.5 size-3.5" aria-hidden="true" />
                Report
              </RouterLink>
            </Button>
          </div>
        </div>
      </div>

      <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Today at a glance">
        <li class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
          <p class="flex items-center justify-between text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5">
              <TrendingUp class="size-3.5 text-chart-2" aria-hidden="true" />
              Today's Attendance
            </span>
            <span v-if="trendLabel(studentsGrowth)" class="text-chart-2">{{ trendLabel(studentsGrowth) }}</span>
          </p>
          <p class="mt-2 text-2xl font-semibold tabular-nums">
            {{ attendanceRate == null ? '—' : `${attendanceRate}%` }}
          </p>
        </li>
        <li class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
          <p class="flex items-center justify-between text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5">
              <UserPlus class="size-3.5 text-primary" aria-hidden="true" />
              New Admissions
            </span>
          </p>
          <p class="mt-2 text-2xl font-semibold tabular-nums">{{ newAdmissions ?? 0 }}</p>
        </li>
        <li class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
          <p class="flex items-center justify-between text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5">
              <Banknote class="size-3.5 text-chart-3" aria-hidden="true" />
              Fees Today
            </span>
            <span v-if="trendLabel(paymentsGrowth)" class="text-chart-2">{{ trendLabel(paymentsGrowth) }}</span>
          </p>
          <p class="mt-2 text-xl font-semibold tabular-nums sm:text-2xl">
            {{ formatCurrency(feesToday ?? 0) }}
          </p>
        </li>
        <li class="rounded-2xl bg-muted/50 p-3 ring-1 ring-border/60">
          <p class="flex items-center justify-between text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5">
              <CalendarDays class="size-3.5 text-primary" aria-hidden="true" />
              Upcoming Events
            </span>
          </p>
          <p class="mt-2 text-2xl font-semibold tabular-nums">{{ String(upcomingEvents ?? 0).padStart(2, '0') }}</p>
        </li>
      </ul>
    </div>
  </section>
</template>
