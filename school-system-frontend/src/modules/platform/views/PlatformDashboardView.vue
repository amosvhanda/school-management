<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Activity,
  AlertTriangle,
  FileText,
  Key,
  ListTodo,
  Megaphone,
  Plug,
  Server,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import {
  platformApi,
  type PlatformLicenseSummary,
  type SchoolLicenseRow,
} from '@/services/api.service'
import { PLATFORM_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

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
  usage?: { active_users_24h?: number; error_events_24h?: number }
  database?: { connected?: boolean }
  server?: { php_version?: string; memory_usage_mb?: number }
}

const { user } = useAuth()
const loading = ref(true)
const error = ref<string | null>(null)
const licenseSummary = ref<PlatformLicenseSummary | null>(null)
const schools = ref<SchoolLicenseRow[]>([])
const health = ref<HealthSnapshot | null>(null)
const operations = ref<OpsLive | null>(null)
const staffTaskCount = ref(0)
const apiClientCount = ref(0)

const metrics = computed(() => operations.value?.metrics ?? {})
const alerts = computed(() => operations.value?.alerts ?? [])

async function load() {
  loading.value = true
  error.value = null
  try {
    const [licensePayload, schoolsPayload, healthData, opsData, tasks, clients] = await Promise.all([
      platformApi.licenseOverview().catch(() => null),
      platformApi.schoolsOverview().catch(() => null),
      platformApi.systemHealth().catch(() => null),
      platformApi.operationsLive().catch(() => null),
      platformApi.staffTasks().catch(() => []),
      platformApi.apiClients().catch(() => []),
    ])
    licenseSummary.value = licensePayload?.summary ?? null
    schools.value = Array.isArray(schoolsPayload?.schools) ? schoolsPayload.schools.slice(0, 8) : []
    health.value = healthData as HealthSnapshot | null
    operations.value = opsData as OpsLive | null
    staffTaskCount.value = Array.isArray(tasks) ? tasks.length : 0
    apiClientCount.value = Array.isArray(clients) ? clients.length : 0
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load platform dashboard')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <Badge variant="secondary" class="mb-2 font-normal">Super Admin</Badge>
      <h1 class="text-2xl font-semibold tracking-tight">
        Welcome, {{ user?.name?.split(' ')[0] ?? 'Admin' }}
      </h1>
      <p class="text-muted-foreground">
        Platform-wide licenses, system health, operations, and tenant overview
      </p>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Licensed schools"
          :value="String(licenseSummary?.schools.licensed ?? 0)"
          :subtitle="`${licenseSummary?.schools.total ?? 0} total · ${licenseSummary?.schools.unlicensed ?? 0} unlicensed`"
          :icon="Key"
          href="/platform/licenses"
        />
        <KpiCard
          title="System status"
          :value="String(health?.status ?? 'Unknown')"
          :subtitle="`${health?.usage?.active_users_24h ?? 0} active users (24h)`"
          :icon="Activity"
          href="/platform/health"
        />
        <KpiCard
          title="Pending workflows"
          :value="String(metrics.pending_workflows ?? 0)"
          :subtitle="`${metrics.escalated_workflows ?? 0} escalated`"
          :icon="Server"
          href="/platform/operations"
          :accent="(metrics.pending_workflows ?? 0) > 0 ? 'warning' : undefined"
        />
        <KpiCard
          title="Overdue staff tasks"
          :value="String(metrics.overdue_staff_tasks ?? 0)"
          :subtitle="`${metrics.open_staff_tasks ?? staffTaskCount} open tasks`"
          :icon="ListTodo"
          href="/platform/staff-tasks"
          :accent="(metrics.overdue_staff_tasks ?? 0) > 0 ? 'danger' : undefined"
        />
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Open alerts"
          :value="String(metrics.open_alerts ?? alerts.length)"
          subtitle="Unresolved operations alerts"
          :icon="AlertTriangle"
          href="/platform/operations"
          :accent="alerts.length > 0 ? 'warning' : undefined"
        />
        <KpiCard
          title="API clients"
          :value="String(apiClientCount)"
          subtitle="Integrations & external access"
          :icon="Plug"
          href="/platform/api-clients"
        />
        <KpiCard
          title="License keys"
          :value="String(licenseSummary?.keys.total ?? 0)"
          :subtitle="`${licenseSummary?.keys.active ?? 0} active · ${licenseSummary?.keys.unused ?? 0} unused`"
          :icon="Key"
          href="/platform/licenses"
        />
        <KpiCard
          title="Error events (24h)"
          :value="String(health?.usage?.error_events_24h ?? 0)"
          :subtitle="health?.database?.connected ? 'Database connected' : 'Database check failed'"
          :icon="Activity"
          href="/platform/health"
          :accent="(health?.usage?.error_events_24h ?? 0) > 0 ? 'danger' : undefined"
        />
      </div>

      <section class="grid gap-4 lg:grid-cols-12">
        <Card class="lg:col-span-7">
          <CardHeader class="flex flex-row items-center justify-between gap-4">
            <div>
              <CardTitle class="text-base">Tenant schools</CardTitle>
              <CardDescription>License status across registered schools</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/platform/licenses">View all</RouterLink>
            </Button>
          </CardHeader>
          <CardContent>
            <div v-if="schools.length" class="divide-y rounded-lg border">
              <div
                v-for="school in schools"
                :key="school.id"
                class="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
              >
                <div>
                  <p class="text-sm font-medium">{{ school.name }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ school.code }} · {{ school.users_count }} users
                  </p>
                </div>
                <Badge
                  :variant="school.license_status === 'active' ? 'default' : 'secondary'"
                  class="font-normal capitalize"
                >
                  {{ school.license_status || 'none' }}
                </Badge>
              </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">No schools registered yet.</p>
          </CardContent>
        </Card>

        <Card class="lg:col-span-5">
          <CardHeader>
            <CardTitle class="text-base">Live alerts</CardTitle>
            <CardDescription>Unresolved platform and school operations alerts</CardDescription>
          </CardHeader>
          <CardContent class="space-y-3">
            <div
              v-for="alert in alerts.slice(0, 6)"
              :key="alert.id"
              class="rounded-lg border p-3"
            >
              <div class="mb-1 flex items-center justify-between gap-2">
                <p class="text-sm font-medium">{{ alert.title || 'Alert' }}</p>
                <Badge variant="outline" class="font-normal capitalize">
                  {{ alert.severity || 'info' }}
                </Badge>
              </div>
              <p class="text-xs text-muted-foreground line-clamp-2">
                {{ alert.message || alert.category || 'No details' }}
              </p>
            </div>
            <p v-if="!alerts.length" class="text-sm text-muted-foreground">
              No open alerts right now.
            </p>
            <Button variant="outline" size="sm" class="w-full" as-child>
              <RouterLink to="/platform/operations">Open operations</RouterLink>
            </Button>
          </CardContent>
        </Card>
      </section>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Licenses</CardTitle>
            <CardDescription>Issue, renew, and revoke school licenses</CardDescription>
          </CardHeader>
          <CardContent>
            <Button variant="outline" as-child>
              <RouterLink to="/platform/licenses">Manage licenses</RouterLink>
            </Button>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Communications</CardTitle>
            <CardDescription>Send and track platform-wide messages</CardDescription>
          </CardHeader>
          <CardContent>
            <Button variant="outline" as-child>
              <RouterLink to="/platform/communications">
                <Megaphone class="mr-2 size-4" aria-hidden="true" />
                Open inbox
              </RouterLink>
            </Button>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Signable documents</CardTitle>
            <CardDescription>E-sign consent forms, policies, and agreements</CardDescription>
          </CardHeader>
          <CardContent>
            <Button variant="outline" as-child>
              <RouterLink to="/platform/documents">
                <FileText class="mr-2 size-4" aria-hidden="true" />
                Manage documents
              </RouterLink>
            </Button>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Scholarships & refunds</CardTitle>
            <CardDescription>Platform-wide financial programs</CardDescription>
          </CardHeader>
          <CardContent class="flex flex-wrap gap-2">
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/platform/scholarships">Scholarships</RouterLink>
            </Button>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/platform/refunds">Refunds</RouterLink>
            </Button>
          </CardContent>
        </Card>
      </div>

      <DashboardModulesGrid
        :groups="PLATFORM_DASHBOARD_MODULE_GROUPS"
        title="Platform modules"
        description="All platform administration areas available to super admin"
      />
    </template>
  </div>
</template>
