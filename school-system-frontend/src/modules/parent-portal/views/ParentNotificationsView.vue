<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useParentPortalScope } from '@/composables/useParentPortalScope'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime, formatRelativeTime } from '@/lib/format'
import { parentPortalApi } from '@/services/index'

interface NotificationRow {
  id: number
  title?: string
  type?: string
  body?: string
  data?: Record<string, unknown> | null
  student?: {
    id?: number
    full_name?: string
    first_name?: string
    last_name?: string
    student_number?: string
  } | null
  read_at?: string | null
  created_at?: string
}

interface NotificationViewModel extends NotificationRow {
  displayTitle: string
  displayBody: string | null
  displayStudent: string | null
  displayType: string | null
  relativeTime: string
  fullTime: string
}

interface ChildOption {
  id: number
  full_name?: string
  fullName?: string
}

const toast = useToast()
const notifications = ref<NotificationRow[]>([])
const children = ref<ChildOption[]>([])
const selectedStudentId = ref('all')
const loading = ref(true)
const error = ref<string | null>(null)
const markingAll = ref(false)
const scopeStore = useParentPortalScope('notifications-filter')

const selectedChildLabel = computed(() => {
  if (selectedStudentId.value === 'all') return 'all linked children'
  const selectedId = Number(selectedStudentId.value)
  const child = children.value.find((item) => item.id === selectedId)
  if (!child) return 'selected child'
  return child.fullName ?? child.full_name ?? `Student #${child.id}`
})

const notificationsView = computed<NotificationViewModel[]>(() =>
  notifications.value.map((note) => {
    const studentName = note.student
      ? (note.student.full_name
        ?? [note.student.first_name, note.student.last_name].filter(Boolean).join(' ').trim()
        ?? null)
      : null

    const dataBody = (() => {
      if (!note.data || typeof note.data !== 'object') return null
      const fromData = note.data['body']
        ?? note.data['message']
        ?? note.data['description']
        ?? null
      if (fromData == null || String(fromData).trim() === '') return null
      return String(fromData)
    })()

    const dataTitle = (() => {
      if (!note.data || typeof note.data !== 'object') return null
      const fromData = note.data['title'] ?? note.data['subject'] ?? null
      if (fromData == null || String(fromData).trim() === '') return null
      return String(fromData)
    })()

    return {
      ...note,
      displayTitle: note.title?.trim() || dataTitle || 'Notification',
      displayBody: note.body?.trim() || dataBody,
      displayStudent: studentName && studentName.trim() ? studentName : null,
      displayType: note.type?.trim() ? note.type : null,
      relativeTime: formatRelativeTime(note.created_at),
      fullTime: formatDateTime(note.created_at),
    }
  }),
)

async function load() {
  loading.value = true
  error.value = null
  try {
    const childRows = await parentPortalApi.children() as ChildOption[]
    children.value = childRows

    if (
      selectedStudentId.value !== 'all'
      && !childRows.some((child) => String(child.id) === selectedStudentId.value)
    ) {
      selectedStudentId.value = 'all'
      scopeStore.write('all')
    }

    notifications.value = await parentPortalApi.notifications(
      selectedStudentId.value === 'all'
        ? undefined
        : { student_id: Number(selectedStudentId.value) },
    ) as NotificationRow[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load notifications')
  } finally {
    loading.value = false
  }
}

async function onChildFilterChange(value: unknown) {
  selectedStudentId.value = value == null ? 'all' : String(value)
  scopeStore.write(selectedStudentId.value)
  await load()
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

onMounted(async () => {
  selectedStudentId.value = scopeStore.read('all')
  await load()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Notifications</h1>
        <p class="text-muted-foreground">Alerts for {{ selectedChildLabel }}</p>
      </div>
      <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
        <Select :model-value="selectedStudentId" @update:model-value="onChildFilterChange">
          <SelectTrigger class="w-full sm:w-[220px]">
            <SelectValue placeholder="Filter by child" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All linked children</SelectItem>
            <SelectItem
              v-for="child in children"
              :key="child.id"
              :value="String(child.id)"
            >
              {{ child.fullName ?? child.full_name ?? `Student #${child.id}` }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Button
          variant="outline"
          class="w-full shrink-0 sm:w-auto"
          :disabled="markingAll || !notifications.some((n) => !n.read_at)"
          @click="markAllRead"
        >
          Mark all read
        </Button>
      </div>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <div v-else class="space-y-3" aria-live="polite">
      <Card
        v-for="note in notificationsView"
        :key="note.id"
        :class="!note.read_at ? 'border-primary/40 bg-muted/20' : ''"
      >
        <CardHeader class="pb-2">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <CardTitle class="text-base">{{ note.displayTitle }}</CardTitle>
            <div class="flex items-center gap-2">
              <Badge v-if="note.displayType" variant="outline" class="capitalize">{{ note.displayType }}</Badge>
              <Badge v-if="!note.read_at" variant="default">Unread</Badge>
            </div>
          </div>
        </CardHeader>
        <CardContent class="space-y-3">
          <p v-if="note.displayBody" class="text-sm text-muted-foreground">{{ note.displayBody }}</p>
          <p v-if="note.displayStudent" class="text-xs text-muted-foreground">
            Student: <span class="font-medium text-foreground">{{ note.displayStudent }}</span>
          </p>
          <div class="flex items-center justify-between gap-2 text-xs text-muted-foreground">
            <time :title="note.fullTime">{{ note.relativeTime }}</time>
            <Button v-if="!note.read_at" variant="ghost" size="sm" @click="markRead(note.id)">
              Mark read
            </Button>
          </div>
        </CardContent>
      </Card>
      <p v-if="!notificationsView.length" class="text-sm text-muted-foreground">No notifications.</p>
    </div>
  </div>
</template>
