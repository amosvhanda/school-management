<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Bell,
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

const { user } = useAuth()
const loading = ref(true)
const error = ref<string | null>(null)
const lastUpdated = ref<Date | null>(null)
const dashboard = ref<PortalDashboard | null>(null)
const children = ref<Child[]>([])

const overviewCards = computed<MetricCard[]>(() => {
  const d = dashboard.value
  if (!d) return []
  return [
    {
      title: 'My children',
      value: d.children_count ?? 0,
      subtitle: 'Enrolled students',
      icon: GraduationCap,
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
        {
          title: 'Open messages',
          value: d.open_communications ?? 0,
          subtitle: 'Active conversations',
          icon: MessageSquare,
          href: '/portal/hub?tab=messages',
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
          title: 'Recent results',
          value: d.recent_results ?? 0,
          subtitle: 'Published in last 30 days',
          icon: FileText,
          href: '/portal/children',
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

async function load() {
  loading.value = true
  error.value = null
  try {
    dashboard.value = await parentPortalApi.dashboard() as PortalDashboard
    children.value = await parentPortalApi.children() as Child[]
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
      subtitle="Overview of your children's school activity, fees, and communications."
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="refresh"
    />

    <PageLoader v-if="loading" label="Loading parent portal…" />
    <ErrorState v-else-if="error" :description="error" @retry="refresh" />

    <template v-else-if="dashboard">
      <MetricBand
        title="Family overview"
        description="The numbers that matter most for your children right now"
        :cards="overviewCards"
      />

      <MetricBand
        title="Recent activity"
        description="Attendance, results, consents, and school notices"
        :cards="activityCards"
      />

      <Card class="border-border/70">
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <div>
            <CardTitle>My children</CardTitle>
            <CardDescription>Quick view — open a child for results, attendance, fees, and discipline</CardDescription>
          </div>
          <Button variant="outline" size="sm" as-child>
            <RouterLink to="/portal/children">View all</RouterLink>
          </Button>
        </CardHeader>
        <CardContent>
          <div v-if="children.length" class="grid gap-4 sm:grid-cols-2">
            <Card
              v-for="child in children"
              :key="child.id"
              class="h-full border-border/70"
            >
              <CardHeader class="pb-2">
                <CardTitle class="text-base">{{ child.fullName ?? child.full_name ?? (child.student_number ? `Student ${child.student_number}` : 'Student') }}</CardTitle>
                <CardDescription>{{ child.class ?? 'Class not assigned' }}</CardDescription>
              </CardHeader>
              <CardContent class="space-y-3">
                <p v-if="child.balance" class="text-sm font-medium">
                  Balance: {{ child.currency ?? 'USD' }} {{ Number(child.balance).toLocaleString() }}
                </p>
                <p v-else class="text-sm text-muted-foreground">No outstanding balance</p>
                <Button variant="outline" size="sm" as-child>
                  <RouterLink :to="{ name: 'parent-child-detail', params: { id: child.id } }">
                    View results, attendance & fees
                  </RouterLink>
                </Button>
              </CardContent>
            </Card>
          </div>
          <p v-else class="text-sm text-muted-foreground">No children linked to your account.</p>
        </CardContent>
      </Card>

      <DashboardModulesGrid
        :groups="PARENT_DASHBOARD_MODULE_GROUPS"
        title="Portal modules"
        description="Everything available in your parent portal"
      />
    </template>
  </div>
</template>
