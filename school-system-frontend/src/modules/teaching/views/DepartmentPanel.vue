<script setup lang="ts">
import { onMounted, ref } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<any>({ department: null, peers: [], shared_resources: [] })

async function load() {
  loading.value = true
  error.value = null
  try {
    data.value = await teacherPortalApi.department()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load department')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Department hub</h2>
      <p class="text-sm text-muted-foreground">Collaborate with department teachers and browse shared assessment materials.</p>
    </div>
    <PageLoader v-if="loading" label="Loading department…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <p class="text-sm">Department: <strong>{{ data.department || 'Not set' }}</strong></p>
      <div class="grid gap-4 lg:grid-cols-2">
        <Card class="border-border/70">
          <CardHeader><CardTitle class="text-base">Department teachers</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <p v-if="!(data.peers || []).length" class="text-sm text-muted-foreground">No peers found.</p>
            <div v-for="p in data.peers || []" :key="p.id" class="rounded-lg border border-border/60 px-3 py-2 text-sm">
              <p class="font-medium">{{ p.name }}</p>
              <p class="text-muted-foreground">{{ p.subject || '—' }} · {{ p.email }}</p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-border/70">
          <CardHeader><CardTitle class="text-base">Shared resources</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <p v-if="!(data.shared_resources || []).length" class="text-sm text-muted-foreground">No shared resources yet. Mark resources as shared from the Resources tab.</p>
            <div v-for="r in data.shared_resources || []" :key="r.id" class="rounded-lg border border-border/60 px-3 py-2 text-sm">
              <p class="font-medium">{{ r.title }}</p>
              <Badge variant="outline" class="capitalize">{{ r.resource_type }}</Badge>
            </div>
          </CardContent>
        </Card>
      </div>
    </template>
  </div>
</template>
