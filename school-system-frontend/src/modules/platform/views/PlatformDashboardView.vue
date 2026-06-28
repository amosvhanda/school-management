<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Activity, Key, Plug, Server } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import DashboardModulesGrid from '@/components/dashboard/DashboardModulesGrid.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { platformApi } from '@/services/api.service'
import { PLATFORM_DASHBOARD_MODULE_GROUPS } from '@/lib/dashboard-modules'

const { user } = useAuth()
const loading = ref(true)
const error = ref<string | null>(null)
const licenseCount = ref(0)
const health = ref<Record<string, unknown> | null>(null)
const operations = ref<Record<string, unknown> | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const [licensePayload, healthData, opsData] = await Promise.all([
      platformApi.licenseOverview().catch(() => null),
      platformApi.systemHealth().catch(() => null),
      platformApi.operationsLive().catch(() => null),
    ])
    licenseCount.value = licensePayload?.summary?.schools.licensed
      ?? licensePayload?.keys?.length
      ?? 0
    health.value = healthData as Record<string, unknown> | null
    operations.value = opsData as Record<string, unknown> | null
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
      <Badge variant="secondary" class="mb-2 font-normal">Platform admin</Badge>
      <h1 class="text-2xl font-semibold tracking-tight">
        Welcome, {{ user?.name?.split(' ')[0] ?? 'Admin' }}
      </h1>
      <p class="text-muted-foreground">System-wide licenses, health, and operations overview</p>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Licensed schools"
          :value="String(licenseCount)"
          subtitle="Schools with active license"
          :icon="Key"
          href="/platform/licenses"
        />
        <KpiCard
          title="System status"
          :value="String(health?.status ?? 'Unknown')"
          subtitle="Infrastructure health"
          :icon="Activity"
          href="/platform/health"
        />
        <KpiCard
          title="Live operations"
          :value="String(operations?.active_jobs ?? operations?.queue_size ?? '—')"
          subtitle="Background jobs & queues"
          :icon="Server"
          href="/platform/operations"
        />
        <KpiCard
          title="API clients"
          subtitle="Integrations & external access"
          value="Manage"
          :icon="Plug"
          href="/platform/api-clients"
        />
      </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
            <CardTitle class="text-base">System health</CardTitle>
            <CardDescription>Monitor services and uptime</CardDescription>
          </CardHeader>
          <CardContent>
            <Button variant="outline" as-child>
              <RouterLink to="/platform/health">View health</RouterLink>
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
        description="All platform administration areas"
        skip-permission-filter
      />
    </template>
  </div>
</template>
