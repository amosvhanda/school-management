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
import KpiCard from '@/components/dashboard/KpiCard.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
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
const dashboard = ref<PortalDashboard | null>(null)
const children = ref<Child[]>([])

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 17) return 'Good afternoon'
  return 'Good evening'
})

async function load() {
  loading.value = true
  error.value = null
  try {
    dashboard.value = await parentPortalApi.dashboard() as PortalDashboard
    children.value = await parentPortalApi.children() as Child[]
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load portal'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <Badge variant="secondary" class="mb-2 font-normal">Parent</Badge>
      <h1 class="text-2xl font-semibold tracking-tight">
        {{ greeting }}, {{ user?.name?.split(' ')[0] ?? 'there' }}
      </h1>
      <p class="text-muted-foreground">Overview of your children's school activity</p>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="dashboard">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="My children"
          :value="String(dashboard.children_count ?? 0)"
          subtitle="Enrolled students"
          :icon="GraduationCap"
          href="/portal/children"
        />
        <KpiCard
          title="Outstanding fees"
          :value="`$${Number(dashboard.outstanding_balance ?? 0).toLocaleString()}`"
          subtitle="Total balance due"
          :icon="TrendingDown"
          accent="warning"
          href="/portal/children"
        />
        <KpiCard
          title="Notifications"
          :value="String(dashboard.unread_notifications ?? 0)"
          subtitle="Unread alerts"
          :icon="Bell"
          :accent="(dashboard.unread_notifications ?? 0) > 0 ? 'danger' : undefined"
          href="/portal/notifications"
        />
        <KpiCard
          title="Open messages"
          :value="String(dashboard.open_communications ?? 0)"
          subtitle="Active conversations"
          :icon="MessageSquare"
          href="/portal/messages"
        />
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Recent absences"
          :value="String(dashboard.recent_absences ?? 0)"
          subtitle="Last 30 days"
          :icon="UserX"
          :accent="(dashboard.recent_absences ?? 0) > 0 ? 'danger' : undefined"
          href="/portal/children"
        />
        <KpiCard
          title="Recent results"
          :value="String(dashboard.recent_results ?? 0)"
          subtitle="Published in last 30 days"
          :icon="FileText"
          href="/portal/children"
        />
        <KpiCard
          title="Consent forms"
          :value="String(dashboard.pending_consent_forms ?? 0)"
          subtitle="Awaiting your response"
          :icon="FileCheck"
          :accent="(dashboard.pending_consent_forms ?? 0) > 0 ? 'warning' : undefined"
          href="/portal/consent"
        />
        <KpiCard
          title="Announcements"
          :value="String(dashboard.recent_announcements ?? 0)"
          subtitle="Last 30 days"
          :icon="Megaphone"
          href="/portal/announcements"
        />
      </div>

      <div v-if="(dashboard.open_discipline ?? 0) > 0" class="grid gap-4 sm:grid-cols-2">
        <KpiCard
          title="Discipline notes"
          :value="String(dashboard.open_discipline ?? 0)"
          subtitle="Recent incidents (90 days)"
          :icon="ShieldAlert"
          accent="danger"
          href="/portal/children"
        />
      </div>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
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
              class="h-full"
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
