<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  Activity,
  AlertTriangle,
  Database,
  GitBranch,
  ListTodo,
  MemoryStick,
  Server,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime } from '@/lib/format'
import { useToast } from '@/composables/useToast'
import { platformApi } from '@/services/api.service'

interface OpsMetrics {
  pending_workflows?: number
  overdue_staff_tasks?: number
  escalated_workflows?: number
  open_staff_tasks?: number
  open_alerts?: number
}

interface OpsAlert {
  id: number
  severity?: string
  category?: string
  title?: string
  message?: string
  school_id?: number | null
  created_at?: string
}

interface OpsLive {
  timestamp?: string
  scope?: string
  alerts?: OpsAlert[]
  metrics?: OpsMetrics
}

interface HealthSnapshot {
  status?: string
  timestamp?: string
  uptime_check?: boolean
  database?: { connected?: boolean }
  usage?: { active_users_24h?: number; error_events_24h?: number }
  server?: { php_version?: string; memory_usage_mb?: number }
}

const route = useRoute()
const toast = useToast()
const loading = ref(true)
const error = ref<string | null>(null)
const resolvingId = ref<number | null>(null)
const ops = ref<OpsLive | null>(null)
const health = ref<HealthSnapshot | null>(null)

const mode = computed(() => (route.name === 'platform-operations' ? 'operations' : 'health'))

const title = computed(() =>
  mode.value === 'operations' ? 'Live operations' : 'System health',
)

const description = computed(() =>
  mode.value === 'operations'
    ? 'Workflows, staff tasks, and unresolved operations alerts across the platform.'
    : 'Infrastructure status, database connectivity, and recent platform usage.',
)

const metrics = computed(() => ops.value?.metrics ?? {})
const alerts = computed(() => ops.value?.alerts ?? [])

function severityVariant(severity?: string): 'default' | 'secondary' | 'destructive' | 'outline' {
  const value = String(severity ?? '').toLowerCase()
  if (value === 'critical' || value === 'high' || value === 'danger') return 'destructive'
  if (value === 'warning' || value === 'medium') return 'secondary'
  return 'outline'
}

async function load() {
  loading.value = true
  error.value = null
  try {
    if (mode.value === 'operations') {
      ops.value = (await platformApi.operationsLive()) as OpsLive
      health.value = null
    } else {
      health.value = (await platformApi.systemHealth()) as HealthSnapshot
      ops.value = null
    }
  } catch (err) {
    error.value = getErrorMessage(err, `Failed to load ${title.value.toLowerCase()}`)
    ops.value = null
    health.value = null
  } finally {
    loading.value = false
  }
}

async function resolveAlert(id: number) {
  resolvingId.value = id
  try {
    await platformApi.resolveAlert(id)
    toast.success('Alert resolved')
    ops.value = {
      ...ops.value,
      alerts: (ops.value?.alerts ?? []).filter((alert) => alert.id !== id),
      metrics: {
        ...ops.value?.metrics,
        open_alerts: Math.max(0, (ops.value?.metrics?.open_alerts ?? 1) - 1),
      },
    }
  } catch (err) {
    toast.error('Could not resolve alert', getErrorMessage(err))
  } finally {
    resolvingId.value = null
  }
}

onMounted(load)
</script>

<template>
  <PageShell :title="title" :description="description" max-width="wide">
    <template #actions>
      <Button variant="outline" size="sm" :disabled="loading" @click="load">
        Refresh
      </Button>
    </template>

    <PageLoader v-if="loading" :label="`Loading ${title.toLowerCase()}`" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="mode === 'operations' && ops">
      <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
        <Badge variant="secondary" class="font-normal capitalize">
          {{ ops.scope || 'platform' }} scope
        </Badge>
        <span v-if="ops.timestamp">Updated {{ formatDateTime(ops.timestamp) }}</span>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Pending workflows"
          :value="String(metrics.pending_workflows ?? 0)"
          subtitle="Awaiting action"
          :icon="GitBranch"
          href="/platform/staff-tasks"
          :accent="(metrics.pending_workflows ?? 0) > 0 ? 'warning' : undefined"
        />
        <KpiCard
          title="Escalated workflows"
          :value="String(metrics.escalated_workflows ?? 0)"
          subtitle="Needs priority review"
          :icon="Server"
          :accent="(metrics.escalated_workflows ?? 0) > 0 ? 'danger' : undefined"
        />
        <KpiCard
          title="Overdue staff tasks"
          :value="String(metrics.overdue_staff_tasks ?? 0)"
          :subtitle="`${metrics.open_staff_tasks ?? 0} open tasks`"
          :icon="ListTodo"
          href="/platform/staff-tasks"
          :accent="(metrics.overdue_staff_tasks ?? 0) > 0 ? 'danger' : undefined"
        />
        <KpiCard
          title="Open alerts"
          :value="String(metrics.open_alerts ?? alerts.length)"
          subtitle="Unresolved operations alerts"
          :icon="AlertTriangle"
          :accent="alerts.length > 0 ? 'warning' : undefined"
        />
      </div>

      <Card class="mt-6">
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <div>
            <CardTitle class="text-base">Operations alerts</CardTitle>
            <CardDescription>Resolve issues as they are handled</CardDescription>
          </div>
          <Button variant="outline" size="sm" as-child>
            <RouterLink to="/platform/staff-tasks">Staff tasks</RouterLink>
          </Button>
        </CardHeader>
        <CardContent>
          <div v-if="alerts.length" class="divide-y rounded-lg border">
            <article
              v-for="alert in alerts"
              :key="alert.id"
              class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between"
            >
              <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-sm font-medium">{{ alert.title || 'Alert' }}</h3>
                  <Badge :variant="severityVariant(alert.severity)" class="font-normal capitalize">
                    {{ alert.severity || 'info' }}
                  </Badge>
                  <Badge v-if="alert.category" variant="outline" class="font-normal capitalize">
                    {{ alert.category }}
                  </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                  {{ alert.message || 'No details provided.' }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ alert.created_at ? formatDateTime(alert.created_at) : '—' }}
                  <span v-if="alert.school_id"> · School #{{ alert.school_id }}</span>
                </p>
              </div>
              <Button
                variant="outline"
                size="sm"
                class="shrink-0"
                :disabled="resolvingId === alert.id"
                @click="resolveAlert(alert.id)"
              >
                {{ resolvingId === alert.id ? 'Resolving…' : 'Resolve' }}
              </Button>
            </article>
          </div>
          <p v-else class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
            No open alerts. Platform operations look clear.
          </p>
        </CardContent>
      </Card>
    </template>

    <template v-else-if="mode === 'health' && health">
      <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
        <Badge
          :variant="health.status === 'healthy' ? 'default' : 'destructive'"
          class="font-normal capitalize"
        >
          {{ health.status || 'unknown' }}
        </Badge>
        <span v-if="health.timestamp">Checked {{ formatDateTime(health.timestamp) }}</span>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Overall status"
          :value="String(health.status ?? 'Unknown')"
          :subtitle="health.uptime_check ? 'Uptime check passed' : 'Uptime check failed'"
          :icon="Activity"
          :accent="health.status === 'healthy' ? 'success' : 'danger'"
        />
        <KpiCard
          title="Database"
          :value="health.database?.connected ? 'Connected' : 'Unavailable'"
          subtitle="Primary connection check"
          :icon="Database"
          :accent="health.database?.connected ? 'success' : 'danger'"
        />
        <KpiCard
          title="Active users (24h)"
          :value="String(health.usage?.active_users_24h ?? 0)"
          subtitle="Accounts with recent activity"
          :icon="Activity"
        />
        <KpiCard
          title="Error events (24h)"
          :value="String(health.usage?.error_events_24h ?? 0)"
          subtitle="Audit events matching errors"
          :icon="AlertTriangle"
          :accent="(health.usage?.error_events_24h ?? 0) > 0 ? 'warning' : undefined"
        />
      </div>

      <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Runtime</CardTitle>
            <CardDescription>Application server details</CardDescription>
          </CardHeader>
          <CardContent class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3">
              <span class="text-muted-foreground">PHP version</span>
              <span class="font-medium">{{ health.server?.php_version || '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3">
              <span class="flex items-center gap-2 text-muted-foreground">
                <MemoryStick class="size-4" aria-hidden="true" />
                Memory usage
              </span>
              <span class="font-medium tabular-nums">
                {{ health.server?.memory_usage_mb != null ? `${health.server.memory_usage_mb} MB` : '—' }}
              </span>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Related</CardTitle>
            <CardDescription>Jump to related platform tools</CardDescription>
          </CardHeader>
          <CardContent class="flex flex-wrap gap-2">
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/platform/operations">Live operations</RouterLink>
            </Button>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/platform/staff-tasks">Staff tasks</RouterLink>
            </Button>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/platform">Dashboard</RouterLink>
            </Button>
          </CardContent>
        </Card>
      </div>
    </template>

    <div
      v-else
      class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
    >
      No status data available.
    </div>
  </PageShell>
</template>
