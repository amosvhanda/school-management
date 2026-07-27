<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate, formatDateTime } from '@/lib/format'
import { formatMoney } from '@/lib/finance-constants'
import { queryClient } from '@/lib/query-client'
import { queryKeys } from '@/lib/query-keys'
import { fetchRolePreview } from '@/services/dashboard.service'
import type { RoleDashboardPreview } from '@/types/dashboard'

const route = useRoute()
const { user } = useAuth()

const role = computed(() => String(route.params.role ?? 'student'))
const loading = ref(true)
const error = ref<string | null>(null)
const preview = ref<RoleDashboardPreview | null>(null)

function formatKpiValue(label: string, value: string | number) {
  if (label.toLowerCase().includes('fee') && typeof value === 'number') {
    return formatMoney(value)
  }
  return value
}

async function load() {
  loading.value = true
  error.value = null
  try {
    preview.value = await queryClient.fetchQuery({
      queryKey: queryKeys.dashboard.rolePreview(user.value?.id, role.value),
      queryFn: () => fetchRolePreview(role.value),
    })
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load dashboard preview')
  } finally {
    loading.value = false
  }
}

onMounted(load)

watch(role, () => {
  void load()
})
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] space-y-8 pb-8">
    <DashboardHero
      :name="user?.name"
      :role="preview?.title ?? `${role} preview`"
      :subtitle="preview?.subtitle ?? 'Admin preview of this role dashboard'"
      :loading="loading"
      :last-updated="null"
      @refresh="load"
    />

    <Card class="border-amber-500/30 bg-amber-500/5">
      <CardContent class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-muted-foreground">
          This is an <span class="font-medium text-foreground">admin preview</span> of the
          {{ role }} experience using school-wide data — not a live signed-in
          {{ role }} session.
        </p>
        <Button variant="outline" size="sm" as-child class="shrink-0">
          <RouterLink to="/">Back to school dashboard</RouterLink>
        </Button>
      </CardContent>
    </Card>

    <DashboardSkeleton v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="preview">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card v-for="kpi in preview.kpis" :key="kpi.label">
          <CardHeader class="pb-2">
            <CardDescription>{{ kpi.label }}</CardDescription>
            <CardTitle class="text-2xl font-semibold tabular-nums tracking-tight">
              {{ formatKpiValue(kpi.label, kpi.value) }}
            </CardTitle>
          </CardHeader>
        </Card>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <Card class="lg:col-span-2">
          <CardHeader class="border-b border-border/60 px-5 pb-4">
            <CardTitle class="text-base font-semibold tracking-tight">What this role sees</CardTitle>
            <CardDescription>Key portal capabilities</CardDescription>
          </CardHeader>
          <CardContent class="px-5 pt-4">
            <ul class="list-disc space-y-2 pl-5 text-sm text-muted-foreground">
              <li v-for="(item, index) in preview.highlights" :key="index">{{ item }}</li>
            </ul>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="border-b border-border/60 px-5 pb-4">
            <CardTitle class="text-base font-semibold tracking-tight">Notice board</CardTitle>
            <CardDescription>Latest announcements</CardDescription>
          </CardHeader>
          <CardContent class="px-5 pt-4">
            <EmptyState
              v-if="!preview.notices.length"
              class="py-6"
              title="No notices"
              description="Published announcements appear here."
            />
            <ul v-else class="space-y-3">
              <li v-for="notice in preview.notices" :key="notice.id">
                <p class="text-sm font-medium">{{ notice.title }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                  <span v-if="notice.date">{{ formatDate(notice.date) }}</span>
                </p>
              </li>
            </ul>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="border-b border-border/60 px-5 pb-4">
          <CardTitle class="text-base font-semibold tracking-tight">Upcoming events</CardTitle>
          <CardDescription>Shared school calendar items</CardDescription>
        </CardHeader>
        <CardContent class="px-5 pt-4">
          <EmptyState
            v-if="!preview.upcoming_events.length"
            class="py-6"
            title="No upcoming events"
            description="Scheduled events will show here."
          />
          <ul v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <li
              v-for="event in preview.upcoming_events"
              :key="event.id"
              class="rounded-lg border border-border/70 bg-muted/20 p-3"
            >
              <p class="text-sm font-medium">{{ event.title }}</p>
              <p class="mt-1 text-xs text-muted-foreground">
                {{ event.starts_at ? formatDateTime(event.starts_at) : '—' }}
              </p>
              <Badge v-if="event.location" variant="outline" class="mt-2">{{ event.location }}</Badge>
            </li>
          </ul>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
