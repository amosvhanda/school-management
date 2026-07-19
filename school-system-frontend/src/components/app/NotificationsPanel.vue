<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Bell,
  CheckCircle2,
  ClipboardList,
  CreditCard,
  FileText,
  Receipt,
  UserPlus,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { formatRelativeTime } from '@/lib/format'
import { isStaffDashboardRole } from '@/lib/permissions'
import { queryClient } from '@/lib/query-client'
import { queryKeys } from '@/lib/query-keys'
import { useAuth } from '@/composables/useAuth'
import { useParentPortalScope } from '@/composables/useParentPortalScope'
import { parentPortalApi } from '@/services/api.service'
import { fetchPendingWorkflows } from '@/services/dashboard.service'
import { getErrorMessage } from '@/lib/api-response'
import { useNotificationStore } from '@/stores/notification.store'
import type { UserRole } from '@/types/auth'

const notificationStore = useNotificationStore()
const { user } = useAuth()

interface ParentNotificationRow {
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

interface ChildOption {
  id: number
  full_name?: string
  fullName?: string
  student_number?: string
}

const iconMap: Record<string, typeof CreditCard> = {
  payment: CreditCard,
  invoice: Receipt,
  student: UserPlus,
  workflow: ClipboardList,
}

const menuOpen = ref(false)
const parentLoading = ref(false)
const parentError = ref<string | null>(null)
const parentNotifications = ref<ParentNotificationRow[]>([])
const parentChildren = ref<ChildOption[]>([])
const selectedStudentId = ref('all')
const staffLoading = ref(false)
const loadedForUserId = ref<number | null>(null)

const role = computed(() => user.value?.role as UserRole | undefined)
const isParent = computed(() => role.value === 'parent')
const isStaff = computed(() => (role.value ? isStaffDashboardRole(role.value) : false))
const showBell = computed(() => isParent.value || isStaff.value)

const scopeStore = useParentPortalScope('notifications-filter')

const activityItems = computed(() =>
  notificationStore.recentActivity.slice(0, 8).map((item) => ({
    ...item,
    time: formatRelativeTime(item.timestamp),
    Icon: iconMap[item.type] ?? FileText,
  })),
)

const parentItems = computed(() =>
  parentNotifications.value.slice(0, 8).map((item) => {
    const dataTitle = item.data?.title ?? item.data?.subject
    const dataBody = item.data?.body ?? item.data?.message ?? item.data?.description
    const studentName = item.student
      ? (item.student.full_name
        ?? [item.student.first_name, item.student.last_name].filter(Boolean).join(' ').trim()
        ?? null)
      : null

    return {
      ...item,
      displayTitle: item.title?.trim() || String(dataTitle ?? 'Notification'),
      displayBody: item.body?.trim() || (dataBody == null ? null : String(dataBody)),
      displayStudent: studentName && studentName.trim() ? studentName : null,
      time: formatRelativeTime(item.created_at),
      Icon: iconMap[item.type ?? ''] ?? FileText,
    }
  }),
)

const selectedChildLabel = computed(() => {
  if (selectedStudentId.value === 'all') return 'All linked children'
  const selectedId = Number(selectedStudentId.value)
  const child = parentChildren.value.find((item) => item.id === selectedId)
  if (!child) return 'Selected child'
  return child.fullName ?? child.full_name ?? (child.student_number ? `Student ${child.student_number}` : 'Student')
})

const badgeTotal = computed(() => {
  if (isParent.value) return notificationStore.parentUnreadCount
  if (isStaff.value) return notificationStore.workflowCount
  return 0
})

const viewAllRoute = computed(() => {
  if (isParent.value) return '/portal/notifications'
  if (isStaff.value) return '/workflows'
  return '/'
})

const viewAllLabel = computed(() => {
  if (isParent.value) return 'View all notifications'
  if (isStaff.value) return 'View pending workflows'
  return 'Go to dashboard'
})

const emptyCopy = computed(() => {
  if (isParent.value) {
    return {
      title: "You're all caught up",
      description: 'No alerts for this profile scope.',
    }
  }
  if (isStaff.value) {
    return {
      title: 'No pending approvals',
      description: 'Workflows waiting on you will show here.',
    }
  }
  return {
    title: 'No notifications',
    description: 'Alerts for your role will appear here when available.',
  }
})

function resetLocalPanelState() {
  parentNotifications.value = []
  parentChildren.value = []
  parentError.value = null
  parentLoading.value = false
  staffLoading.value = false
  selectedStudentId.value = 'all'
  loadedForUserId.value = null
}

async function refreshParentUnreadBadge() {
  if (!isParent.value || !user.value?.id) return

  try {
    const unread = await queryClient.fetchQuery({
      queryKey: queryKeys.parent.unread(user.value.id),
      staleTime: 60_000,
      queryFn: async () => {
        const rows = await parentPortalApi.notifications({
          unread_only: true,
          limit: 50,
        }) as ParentNotificationRow[]
        return rows.length
      },
    })
    notificationStore.setParentUnreadCount(unread)
  } catch {
    // Badge is best-effort; opening the panel still loads the full list.
  }
}

async function loadParentNotifications() {
  if (!isParent.value || !user.value?.id) return

  parentLoading.value = true
  parentError.value = null

  try {
    if (!parentChildren.value.length || loadedForUserId.value !== user.value.id) {
      parentChildren.value = await parentPortalApi.children() as ChildOption[]
    }

    if (
      selectedStudentId.value !== 'all'
      && !parentChildren.value.some((child) => String(child.id) === selectedStudentId.value)
    ) {
      selectedStudentId.value = 'all'
      scopeStore.write('all')
    }

    const notifications = await parentPortalApi.notifications(
      selectedStudentId.value === 'all'
        ? { limit: 20 }
        : { student_id: Number(selectedStudentId.value), limit: 20 },
    ) as ParentNotificationRow[]

    parentNotifications.value = notifications
    loadedForUserId.value = user.value.id

    const unread = notifications.filter((item) => !item.read_at).length
    // When filtered to one child, refresh global unread separately so the badge stays honest.
    if (selectedStudentId.value === 'all') {
      notificationStore.setParentUnreadCount(unread)
      queryClient.setQueryData(queryKeys.parent.unread(user.value.id), unread)
    } else {
      await refreshParentUnreadBadge()
    }
  } catch (error) {
    parentError.value = getErrorMessage(error, 'Failed to load notifications')
  } finally {
    parentLoading.value = false
  }
}

async function ensureStaffWorkflowBadge() {
  if (!isStaff.value || !user.value?.id) return

  staffLoading.value = true
  try {
    const workflows = await queryClient.fetchQuery({
      queryKey: queryKeys.dashboard.workflows(user.value.id),
      staleTime: 60_000,
      queryFn: fetchPendingWorkflows,
    })
    notificationStore.setWorkflowCount(workflows.length)
  } catch {
    // Keep the panel responsive even if workflows fail.
  } finally {
    staffLoading.value = false
  }
}

async function onChildFilterChange(value: unknown) {
  selectedStudentId.value = value == null ? 'all' : String(value)
  scopeStore.write(selectedStudentId.value)
  await loadParentNotifications()
}

async function markRead(id: number) {
  const row = parentNotifications.value.find((item) => item.id === id)
  if (!row || row.read_at) return

  row.read_at = new Date().toISOString()
  notificationStore.decrementParentUnread(1)
  if (user.value?.id) {
    const current = queryClient.getQueryData<number>(queryKeys.parent.unread(user.value.id))
    if (typeof current === 'number') {
      queryClient.setQueryData(queryKeys.parent.unread(user.value.id), Math.max(0, current - 1))
    }
  }

  try {
    await parentPortalApi.markNotificationRead(id)
  } catch {
    row.read_at = null
    notificationStore.setParentUnreadCount(notificationStore.parentUnreadCount + 1)
  }
}

watch(
  () => user.value?.id,
  (id, previous) => {
    notificationStore.bindSession(id ?? null)
    if (id !== previous) {
      resetLocalPanelState()
      selectedStudentId.value = scopeStore.read('all')
      if (isParent.value) {
        void refreshParentUnreadBadge()
      }
    }
  },
  { immediate: true },
)

watch(
  () => scopeStore.storageKey.value,
  () => {
    selectedStudentId.value = scopeStore.read('all')
  },
  { immediate: true },
)

watch(
  () => menuOpen.value,
  async (open) => {
    if (!open) return
    if (isParent.value) {
      await loadParentNotifications()
      return
    }
    if (isStaff.value) {
      await ensureStaffWorkflowBadge()
    }
  },
)

onMounted(() => {
  if (isParent.value) {
    void refreshParentUnreadBadge()
  }
})
</script>

<template>
  <DropdownMenu v-if="showBell" v-model:open="menuOpen">
    <DropdownMenuTrigger as-child>
      <Button
        variant="ghost"
        size="icon"
        class="relative size-9"
        aria-label="Open notifications"
      >
        <Bell class="size-4" aria-hidden="true" />
        <span
          v-if="badgeTotal > 0"
          class="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground animate-in zoom-in-50 duration-200"
          :aria-label="`${badgeTotal} unread notifications`"
        >
          {{ badgeTotal > 9 ? '9+' : badgeTotal }}
        </span>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent
      align="end"
      class="flex w-80 max-h-[min(28rem,var(--reka-dropdown-menu-content-available-height,90vh))] flex-col overflow-hidden rounded-xl border p-0 shadow-lg"
    >
      <DropdownMenuLabel class="flex shrink-0 items-center justify-between px-4 py-3 text-sm font-semibold">
        <span>Notifications</span>
        <Badge v-if="isParent && notificationStore.parentUnreadCount" variant="secondary" class="text-xs font-normal">
          {{ notificationStore.parentUnreadCount }} unread
        </Badge>
        <Badge v-else-if="isStaff && notificationStore.workflowCount" variant="secondary" class="text-xs font-normal">
          {{ notificationStore.workflowCount }} pending
        </Badge>
      </DropdownMenuLabel>

      <template v-if="isParent">
        <div class="shrink-0 px-3 pb-2">
          <p class="mb-1 text-[11px] uppercase tracking-wide text-muted-foreground">Scope</p>
          <Select :model-value="selectedStudentId" @update:model-value="onChildFilterChange">
            <SelectTrigger class="h-8 text-xs">
              <SelectValue :placeholder="selectedChildLabel" />
            </SelectTrigger>
            <SelectContent class="z-[100]">
              <SelectItem value="all">All linked children</SelectItem>
              <SelectItem
                v-for="child in parentChildren"
                :key="child.id"
                :value="String(child.id)"
              >
                {{ child.fullName ?? child.full_name ?? (child.student_number ? `Student ${child.student_number}` : 'Student') }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </template>

      <DropdownMenuSeparator class="shrink-0" />

      <ScrollArea class="min-h-0 flex-1">
        <div
          v-if="(isParent && parentLoading) || (isStaff && staffLoading && !activityItems.length)"
          class="px-4 py-6 text-center text-sm text-muted-foreground"
        >
          Loading…
        </div>

        <div
          v-else-if="isParent && parentError"
          class="px-4 py-6 text-center text-sm text-destructive"
        >
          {{ parentError }}
        </div>

        <div
          v-else-if="isParent ? !parentItems.length : !activityItems.length && !notificationStore.workflowCount"
          class="flex flex-col items-center gap-2 px-4 py-8 text-center"
        >
          <CheckCircle2 class="size-8 text-muted-foreground/40" aria-hidden="true" />
          <p class="text-sm font-medium text-foreground">{{ emptyCopy.title }}</p>
          <p class="max-w-[200px] text-xs leading-normal text-muted-foreground">
            {{ emptyCopy.description }}
          </p>
        </div>

        <div v-else-if="isParent" class="divide-y divide-muted/60">
          <div
            v-for="item in parentItems"
            :key="String(item.id)"
            class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
          >
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <component :is="item.Icon" class="size-3.5" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1 space-y-1">
              <p class="text-sm font-medium leading-snug text-foreground">{{ item.displayTitle }}</p>
              <p v-if="item.displayBody" class="line-clamp-2 text-xs text-muted-foreground">{{ item.displayBody }}</p>
              <p class="text-xs text-muted-foreground">
                {{ item.displayStudent ?? 'Parent portal' }} · {{ item.time }}
              </p>
              <Button
                v-if="!item.read_at"
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-[11px]"
                @click="markRead(item.id)"
              >
                Mark read
              </Button>
            </div>
          </div>
        </div>

        <div v-else class="divide-y divide-muted/60">
          <div
            v-if="notificationStore.workflowCount"
            class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
          >
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <ClipboardList class="size-3.5" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium leading-snug text-foreground">
                {{ notificationStore.workflowCount }} workflow{{ notificationStore.workflowCount === 1 ? '' : 's' }} need your approval
              </p>
              <p class="mt-0.5 text-xs text-muted-foreground">Personal to your role</p>
            </div>
          </div>
          <div
            v-for="item in activityItems"
            :key="String(item.id)"
            class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
          >
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <component :is="item.Icon" class="size-3.5" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium leading-snug text-foreground">{{ item.description }}</p>
              <p class="mt-0.5 text-xs text-muted-foreground">{{ item.user }} · {{ item.time }}</p>
            </div>
          </div>
        </div>
      </ScrollArea>

      <DropdownMenuSeparator class="shrink-0" />
      <div class="shrink-0 border-t border-border/40 bg-muted/5 p-2">
        <Button variant="ghost" size="sm" class="h-8 w-full justify-center text-xs font-medium" as-child>
          <RouterLink :to="viewAllRoute">{{ viewAllLabel }}</RouterLink>
        </Button>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
