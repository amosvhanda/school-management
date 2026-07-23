<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const items = ref<any[]>([])

async function load() {
  loading.value = true
  error.value = null
  try {
    items.value = (await teacherPortalApi.notifications()) as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load notifications')
  } finally {
    loading.value = false
  }
}

async function markRead(id: number) {
  try {
    await teacherPortalApi.markNotificationRead(id)
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

async function markAll() {
  try {
    await teacherPortalApi.markAllNotificationsRead()
    toast.success('All marked read')
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-lg font-semibold">Notifications</h2>
        <p class="text-sm text-muted-foreground">Assignment submissions, attendance reminders, exam reminders, parent messages, approvals, and announcements.</p>
      </div>
      <Button variant="outline" size="sm" @click="markAll">Mark all read</Button>
    </div>
    <PageLoader v-if="loading" label="Loading notifications…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <p v-if="!items.length" class="py-8 text-center text-sm text-muted-foreground">No notifications yet.</p>
      <Card v-for="n in items" :key="n.id" class="border-border/70" :class="!n.read_at && 'bg-muted/30'">
        <CardContent class="flex flex-col gap-2 py-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <p class="font-medium">{{ n.title }}</p>
              <Badge variant="outline">{{ n.type }}</Badge>
              <Badge v-if="!n.read_at" variant="secondary">Unread</Badge>
            </div>
            <p class="text-sm text-muted-foreground">{{ n.body }}</p>
          </div>
          <Button v-if="!n.read_at" size="sm" variant="outline" @click="markRead(n.id)">Mark read</Button>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
