<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { Component } from 'vue'
import {
  ArrowUpRight,
  BookOpen,
  ClipboardCheck,
  GraduationCap,
  Receipt,
  UserPlus,
  Wallet,
} from '@lucide/vue'
import { Card, CardContent } from '@/components/ui/card'
import { useAuth } from '@/composables/useAuth'
import type { NavCapability } from '@/types/navigation'
import { cn } from '@/lib/utils'
import DashboardSection from '@/components/dashboard/DashboardSection.vue'

interface QuickAction {
  label: string
  description: string
  href: string
  icon: Component
  accent?: string
  capability: NavCapability
}

const allActions: QuickAction[] = [
  {
    label: 'Add student',
    description: 'Register a new learner',
    href: '/students?create=1',
    icon: GraduationCap,
    accent: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    capability: 'canManageStudents',
  },
  {
    label: 'Enrollment',
    description: 'Review applications',
    href: '/enrollment',
    icon: UserPlus,
    accent: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    capability: 'canManageStudents',
  },
  {
    label: 'Take attendance',
    description: "Today's register",
    href: '/academics/attendance',
    icon: ClipboardCheck,
    accent: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    capability: 'canManageStudents',
  },
  {
    label: 'Gradebook',
    description: 'Enter marks',
    href: '/academics/grades',
    icon: BookOpen,
    accent: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
    capability: 'canManageExaminations',
  },
  {
    label: 'Record payment',
    description: 'Fees & collections',
    href: '/finance/payments?create=1',
    icon: Wallet,
    accent: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    capability: 'canManageFinance',
  },
  {
    label: 'New invoice',
    description: 'Bill a student',
    href: '/finance/invoices?create=1',
    icon: Receipt,
    accent: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
    capability: 'canManageFinance',
  },
]

const { checkCapability } = useAuth()

const actions = computed(() =>
  allActions.filter((action) => checkCapability(action.capability)),
)
</script>

<template>
  <section v-if="actions.length" aria-labelledby="quick-actions-title" class="space-y-4">
    <DashboardSection
      title-id="quick-actions-title"
      title="Quick actions"
      description="Jump to common daily workflows"
    />
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
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
