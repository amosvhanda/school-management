<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { Component } from 'vue'
import {
  ArrowUpRight,
  Banknote,
  BarChart3,
  BookOpen,
  CalendarDays,
  ClipboardCheck,
  CreditCard,
  FileText,
  GraduationCap,
  MessageSquare,
  NotebookPen,
  Receipt,
  UserPlus,
  Wallet,
} from '@lucide/vue'
import type { StaffDashboardVariant } from '@/lib/role-dashboard'
import { useAuth } from '@/composables/useAuth'
import type { NavCapability } from '@/types/navigation'
import { cn } from '@/lib/utils'
import { Card, CardContent } from '@/components/ui/card'
import DashboardSection from './DashboardSection.vue'

interface QuickAction {
  label: string
  description: string
  href: string
  icon: Component
  accent?: string
  capability?: NavCapability
  variants: StaffDashboardVariant[]
}

const props = defineProps<{
  variant: StaffDashboardVariant
}>()

const allActions: QuickAction[] = [
  {
    label: 'Add student',
    description: 'Register a learner',
    href: '/students?create=1',
    icon: GraduationCap,
    accent: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    capability: 'canManageStudents',
    variants: ['admin'],
  },
  {
    label: 'Enrollment',
    description: 'Review applications',
    href: '/enrollment',
    icon: UserPlus,
    accent: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    capability: 'canManageStudents',
    variants: ['admin'],
  },
  {
    label: 'Take attendance',
    description: "Today's register",
    href: '/academics/attendance',
    icon: ClipboardCheck,
    accent: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    capability: 'canManageStudents',
    variants: ['admin', 'teacher'],
  },
  {
    label: 'Timetable',
    description: 'Class schedule',
    href: '/academics/timetable',
    icon: CalendarDays,
    accent: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    capability: 'canManageTeachers',
    variants: ['admin'],
  },
  {
    label: 'My timetable',
    description: 'Your teaching schedule',
    href: '/academics/my-timetable',
    icon: CalendarDays,
    accent: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    variants: ['teacher'],
  },
  {
    label: 'Students',
    description: 'Learner records',
    href: '/students',
    icon: GraduationCap,
    accent: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    capability: 'canManageStudents',
    variants: ['teacher'],
  },
  {
    label: 'Messages',
    description: 'Parent & staff threads',
    href: '/communications/threads',
    icon: MessageSquare,
    accent: 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400',
    capability: 'isStaff',
    variants: ['teacher', 'accounts'],
  },
  {
    label: 'Gradebook',
    description: 'Enter marks',
    href: '/academics/grades',
    icon: BookOpen,
    accent: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
    capability: 'canManageExaminations',
    variants: ['admin', 'examination_officer'],
  },
  {
    label: 'Enter exam results',
    description: 'Marks for your subjects',
    href: '/academics/exams',
    icon: FileText,
    accent: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    capability: 'canEnterExamResults',
    variants: ['teacher'],
  },
  {
    label: 'Examinations',
    description: 'Schedule & publish',
    href: '/academics/exams',
    icon: FileText,
    accent: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    capability: 'canManageExaminations',
    variants: ['admin', 'examination_officer'],
  },
  {
    label: 'Tests',
    description: 'Class assessments',
    href: '/academics/tests',
    icon: NotebookPen,
    accent: 'bg-fuchsia-500/10 text-fuchsia-600 dark:text-fuchsia-400',
    capability: 'canManageExaminations',
    variants: ['examination_officer'],
  },
  {
    label: 'Record payment',
    description: 'Fees & collections',
    href: '/finance/payments?create=1',
    icon: Wallet,
    accent: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    capability: 'canManageFinance',
    variants: ['admin', 'finance', 'accounts'],
  },
  {
    label: 'New invoice',
    description: 'Bill a student',
    href: '/finance/invoices?create=1',
    icon: Receipt,
    accent: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
    capability: 'canManageFinance',
    variants: ['admin', 'finance', 'accounts'],
  },
  {
    label: 'Finance overview',
    description: 'Summary & KPIs',
    href: '/finance',
    icon: CreditCard,
    accent: 'bg-orange-500/10 text-orange-600 dark:text-orange-400',
    capability: 'canManageFinance',
    variants: ['finance', 'accounts'],
  },
  {
    label: 'Payroll',
    description: 'Staff salaries',
    href: '/finance/payroll',
    icon: Banknote,
    accent: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400',
    capability: 'canManageFinance',
    variants: ['finance', 'accounts'],
  },
  {
    label: 'Aging report',
    description: 'Outstanding fees',
    href: '/finance/reports',
    icon: BarChart3,
    accent: 'bg-teal-500/10 text-teal-600 dark:text-teal-400',
    capability: 'canManageFinance',
    variants: ['finance', 'accounts'],
  },
  {
    label: 'Fee structures',
    description: 'Configure school fees',
    href: '/finance/fees',
    icon: Wallet,
    accent: 'bg-lime-500/10 text-lime-700 dark:text-lime-400',
    capability: 'canManageFinance',
    variants: ['finance'],
  },
  {
    label: 'Transactions',
    description: 'Ledger activity',
    href: '/finance/transactions',
    icon: ArrowUpRight,
    accent: 'bg-slate-500/10 text-slate-600 dark:text-slate-400',
    capability: 'canManageFinance',
    variants: ['accounts'],
  },
]

const { checkCapability } = useAuth()

const actions = computed(() =>
  allActions.filter(
    (action) =>
      action.variants.includes(props.variant)
      && (!action.capability || checkCapability(action.capability)),
  ),
)
</script>

<template>
  <section v-if="actions.length" aria-labelledby="quick-actions-title" class="space-y-4">
    <DashboardSection
      title-id="quick-actions-title"
      title="Quick actions"
      description="Shortcuts for your role"
    />
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <RouterLink
        v-for="action in actions"
        :key="action.href"
        :to="action.href"
        class="group rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <Card class="h-full transition-colors hover:bg-muted/30">
          <CardContent class="flex flex-col gap-3 px-4 py-4">
            <div class="flex items-start justify-between gap-2">
              <div
                :class="cn(
                  'flex size-9 items-center justify-center rounded-lg',
                  action.accent ?? 'bg-muted text-muted-foreground',
                )"
              >
                <component :is="action.icon" class="size-4" aria-hidden="true" />
              </div>
              <ArrowUpRight
                class="size-3.5 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                aria-hidden="true"
              />
            </div>
            <div>
              <p class="text-sm font-medium leading-none">{{ action.label }}</p>
              <p class="mt-1.5 text-xs text-muted-foreground">{{ action.description }}</p>
            </div>
          </CardContent>
        </Card>
      </RouterLink>
    </div>
  </section>
</template>
