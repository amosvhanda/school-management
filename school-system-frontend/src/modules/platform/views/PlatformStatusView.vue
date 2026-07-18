<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { getErrorMessage } from '@/lib/api-response'
import { platformApi } from '@/services/api.service'

const route = useRoute()
const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<Record<string, unknown> | null>(null)

const mode = computed(() => (route.name === 'platform-operations' ? 'operations' : 'health'))

const title = computed(() =>
  mode.value === 'operations' ? 'Live operations' : 'System health',
)

const description = computed(() =>
  mode.value === 'operations'
    ? 'Background jobs, queues, and platform runtime status.'
    : 'Infrastructure and service health for the platform.',
)

const entries = computed(() => {
  if (!data.value) return []
  return Object.entries(data.value).map(([key, value]) => ({
    key,
    label: key.replace(/_/g, ' '),
    value: formatValue(value),
  }))
})

function formatValue(value: unknown): string {
  if (value == null) return '—'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (typeof value === 'object') {
    try {
      return JSON.stringify(value)
    } catch {
      return String(value)
    }
  }
  return String(value)
}

async function load() {
  loading.value = true
  error.value = null
  try {
    data.value = mode.value === 'operations'
      ? (await platformApi.operationsLive()) as Record<string, unknown>
      : (await platformApi.systemHealth()) as Record<string, unknown>
  } catch (err) {
    error.value = getErrorMessage(err, `Failed to load ${title.value.toLowerCase()}`)
    data.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell :title="title" :description="description" max-width="wide">
    <PageLoader v-if="loading" :label="`Loading ${title.toLowerCase()}`" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <div v-else-if="!entries.length" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
      No status data available.
    </div>
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <Card v-for="entry in entries" :key="entry.key">
        <CardHeader class="pb-2">
          <CardDescription class="capitalize">{{ entry.label }}</CardDescription>
          <CardTitle class="text-base font-medium break-words">{{ entry.value }}</CardTitle>
        </CardHeader>
        <CardContent class="sr-only">{{ entry.label }}</CardContent>
      </Card>
    </div>
  </PageShell>
</template>
