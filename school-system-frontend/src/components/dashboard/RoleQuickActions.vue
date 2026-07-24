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
import { canShowDashboardItem } from '@/lib/dashboard-access'
import type { NavCapability } from '@/types/navigation'
import { Card, CardContent } from '@/components/ui/card'
import DashboardSection from './DashboardSection.vue'
import { useRouter } from 'vue-router'

interface QuickAction {
  label: string
  description: string
  href: string
  icon: Component
  capability?: NavCapability | NavCapability[]
  variants: StaffDashboardVariant[]
}

const props = defineProps<{
  variant: StaffDashboardVariant
}>()

const allActions: QuickAction[] = [
  {
    label: 'Add student',
    description: 'Register a learner',
    href: '/people?tab=students&create=1',
    icon: GraduationCap,
    capability: 'canManageStudents',
    variants: ['admin'],
  },
  {
    label: 'Enrollment',
    description: 'Review applications',
    href: '/people?tab=enrollment',
    icon: UserPlus,
    capability: 'canManageStudents',
    variants: ['admin'],
  },
  {
    label: 'Take attendance',
    description: "Today's register",
    href: '/academics/attendance',
    icon: ClipboardCheck,
    capability: 'canManageStudents',
    variants: ['admin', 'teacher'],
  },
  {
    label: 'Teaching workspace',
    description: 'Lessons, homework, and class tools',
    href: '/teaching',
    icon: NotebookPen,
    capability: 'isStaff',
    variants: ['teacher'],
  },
  {
    label: 'Timetable',
    description: 'Class schedule',
    href: '/academics/timetable',
    icon: CalendarDays,
    capability: 'canManageTeachers',
    variants: ['admin'],
  },
  {
    label: 'My timetable',
    description: 'Your teaching schedule',
    href: '/academics/my-timetable',
    icon: CalendarDays,
    capability: 'isStaff',
    variants: ['teacher'],
  },
  {
    label: 'Homework',
    description: 'Submissions and grading',
    href: '/teaching?tab=homework',
    icon: NotebookPen,
    capability: 'isStaff',
    variants: ['teacher'],
  },
  {
    label: 'Messages',
    description: 'Parent & staff threads',
    href: '/communications?tab=messages',
    icon: MessageSquare,
    capability: 'isStaff',
    variants: ['teacher', 'accounts', 'finance'],
  },
  {
    label: 'Gradebook',
    description: 'Enter marks',
    href: '/academics/grades',
    icon: BookOpen,
    capability: ['canManageExaminations', 'canEnterExamResults'],
    variants: ['admin', 'examination_officer', 'teacher'],
  },
  {
    label: 'Enter exam results',
    description: 'Marks for your subjects',
    href: '/academics/exams',
    icon: FileText,
    capability: 'canEnterExamResults',
    variants: ['teacher'],
  },
  {
    label: 'Examinations',
    description: 'Schedule & publish',
    href: '/academics/exams',
    icon: FileText,
    capability: 'canManageExaminations',
    variants: ['admin', 'examination_officer'],
  },
  {
    label: 'Tests',
    description: 'Class assessments',
    href: '/academics/tests',
    icon: NotebookPen,
    capability: 'canManageExaminations',
    variants: ['examination_officer'],
  },
  {
    label: 'Record payment',
    description: 'Fees & collections',
    href: '/finance?tab=payments&create=1',
    icon: Wallet,
    capability: 'canManageFinance',
    variants: ['admin', 'finance', 'accounts'],
  },
  {
    label: 'New invoice',
    description: 'Bill a student',
    href: '/finance?tab=invoices&create=1',
    icon: Receipt,
    capability: 'canManageFinance',
    variants: ['admin', 'finance', 'accounts'],
  },
  {
    label: 'Finance overview',
    description: 'Summary & KPIs',
    href: '/finance',
    icon: CreditCard,
    capability: 'canManageFinance',
    variants: ['finance', 'accounts'],
  },
  {
    label: 'Payroll',
    description: 'Staff salaries',
    href: '/finance?tab=payroll',
    icon: Banknote,
    capability: 'canManageFinance',
    variants: ['finance', 'accounts'],
  },
  {
    label: 'Aging report',
    description: 'Outstanding fees',
    href: '/finance?tab=aging',
    icon: BarChart3,
    capability: 'canManageFinance',
    variants: ['finance', 'accounts'],
  },
  {
    label: 'Fee structures',
    description: 'Configure school fees',
    href: '/finance?tab=fees',
    icon: Wallet,
    capability: 'canManageFinance',
    variants: ['finance'],
  },
  {
    label: 'Transactions',
    description: 'Ledger activity',
    href: '/finance?tab=transactions',
    icon: ArrowUpRight,
    capability: 'canManageFinance',
    variants: ['accounts'],
  },
  {
    label: 'Audit trail',
    description: 'System activity log',
    href: '/hr?tab=audit',
    icon: BarChart3,
    capability: 'canViewAuditLogs',
    variants: ['finance', 'accounts'],
  },
]

const { user } = useAuth()
const router = useRouter()

const actions = computed(() =>
  allActions.filter(
    (action) =>
      action.variants.includes(props.variant)
      && canShowDashboardItem(
        user.value,
        {
          href: action.href,
          capability: action.capability,
        },
        router,
      ),
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
              <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
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
