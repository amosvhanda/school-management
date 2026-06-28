<script setup lang="ts">
import { onMounted, ref } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { parentPortalApi } from '@/services/index'

interface NotificationRow {
  id: number
  title?: string
  type?: string
  body?: string
  read_at?: string | null
  created_at?: string
}

const toast = useToast()
const notifications = ref<NotificationRow[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const markingAll = ref(false)

async function load() {
  loading.value = true
  error.value = null
  try {
    notifications.value = await parentPortalApi.notifications() as NotificationRow[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load notifications')
  } finally {
    loading.value = false
  }
}

async function markRead(id: number) {
  try {
    await parentPortalApi.markNotificationRead(id)
    await load()
  } catch (err) {
    toast.error('Could not mark as read', getErrorMessage(err))
  }
}

async function markAllRead() {
  markingAll.value = true
  try {
    await parentPortalApi.markAllNotificationsRead()
    toast.success('All notifications marked as read')
    await load()
  } catch (err) {
    toast.error('Could not update notifications', getErrorMessage(err))
  } finally {
    markingAll.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Notifications</h1>
        <p class="text-muted-foreground">Alerts about your children's school activity</p>
      </div>
      <Button
        variant="outline"
        :disabled="markingAll || !notifications.some((n) => !n.read_at)"
        @click="markAllRead"
      >
        Mark all read
      </Button>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <div v-else class="space-y-3" aria-live="polite">
      <Card
        v-for="note in notifications"
        :key="note.id"
        :class="!note.read_at ? 'border-primary/40 bg-muted/20' : ''"
      >
        <CardHeader class="pb-2">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <CardTitle class="text-base">{{ note.title ?? 'Notification' }}</CardTitle>
            <div class="flex items-center gap-2">
              <Badge v-if="note.type" variant="outline" class="capitalize">{{ note.type }}</Badge>
              <Badge v-if="!note.read_at" variant="default">Unread</Badge>
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-3">
          <p v-if="note.body" class="text-sm text-muted-foreground">{{ note.body }}</p>
          <div class="flex items-center justify-between gap-2 text-xs text-muted-foreground">
            <time>{{ note.created_at ?? '' }}</time>
            <Button v-if="!note.read_at" variant="ghost" size="sm" @click="markRead(note.id)">
              Mark read
            </Button>
          </div>
        </CardContent>
      </Card>
      <p v-if="!notifications.length" class="text-sm text-muted-foreground">No notifications.</p>
    </div>
  </div>
</template>
