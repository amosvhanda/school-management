<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<any>({ open: [], mine: [] })

async function load() {
  loading.value = true
  error.value = null
  try {
    data.value = await teacherPortalApi.substitutions()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load substitutions')
  } finally {
    loading.value = false
  }
}

async function accept(id: number) {
  try {
    await teacherPortalApi.acceptSubstitution(id)
    toast.success('Cover class accepted')
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Substitution / relief classes</h2>
      <p class="text-sm text-muted-foreground">View open cover requests, accept replacement classes, and track assigned cover lessons.</p>
    </div>
    <PageLoader v-if="loading" label="Loading cover classes…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <section class="space-y-3">
        <h3 class="text-sm font-medium text-muted-foreground">Open requests</h3>
        <p v-if="!(data.open || []).length" class="text-sm text-muted-foreground">No open cover requests.</p>
        <Card v-for="s in data.open || []" :key="s.id" class="border-border/70">
          <CardContent class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="font-medium">{{ s.class_model?.name || `Class #${s.class_id}` }} · {{ formatDate(s.date) }}</p>
              <p class="text-sm text-muted-foreground">Absent: {{ s.absent_teacher?.name || '—' }} · {{ s.period || 'Full day' }}</p>
            </div>
            <Button size="sm" @click="accept(s.id)">Accept cover</Button>
          </CardContent>
        </Card>
      </section>
      <section class="space-y-3">
        <h3 class="text-sm font-medium text-muted-foreground">My cover / missed lessons</h3>
        <Card v-for="s in data.mine || []" :key="'m'+s.id" class="border-border/70">
          <CardContent class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
            <span>{{ s.class_model?.name }} · {{ formatDate(s.date) }}</span>
            <Badge variant="outline" class="capitalize">{{ s.status }}</Badge>
          </CardContent>
        </Card>
        <p v-if="!(data.mine || []).length" class="text-sm text-muted-foreground">No cover history yet.</p>
      </section>
    </template>
  </div>
</template>
