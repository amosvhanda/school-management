import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { toast } from 'vue-sonner'
import type { RecentActivityItem } from '@/types/dashboard'

export interface ToastPayload {
  title: string
  description?: string
  variant?: 'default' | 'destructive' | 'success' | 'warning'
}

/**
 * In-app notification shell state.
 * - Parents: unreadCount from parent_notifications (user-scoped API)
 * - Staff: workflowCount from pending workflows (approver-scoped)
 * - recentActivity is school feed for staff dashboards, not a personal inbox
 */
export const useNotificationStore = defineStore('notification', () => {
  const sessionUserId = ref<number | null>(null)
  const workflowCount = ref(0)
  const parentUnreadCount = ref(0)
  const recentActivity = ref<RecentActivityItem[]>([])

  function notify(payload: ToastPayload) {
    if (payload.variant === 'destructive') {
      toast.error(payload.title, { description: payload.description })
    } else if (payload.variant === 'success') {
      toast.success(payload.title, { description: payload.description })
    } else if (payload.variant === 'warning') {
      toast.warning(payload.title, { description: payload.description })
    } else {
      toast(payload.title, { description: payload.description })
    }
  }

  /** Bind store to the signed-in user; clears previous user's data on switch. */
  function bindSession(userId: number | null) {
    if (sessionUserId.value === userId) return
    sessionUserId.value = userId
    workflowCount.value = 0
    parentUnreadCount.value = 0
    recentActivity.value = []
  }

  function setWorkflowCount(count: number) {
    workflowCount.value = Math.max(0, count)
  }

  function setParentUnreadCount(count: number) {
    parentUnreadCount.value = Math.max(0, count)
  }

  function decrementParentUnread(by = 1) {
    parentUnreadCount.value = Math.max(0, parentUnreadCount.value - by)
  }

  function setRecentActivity(items: RecentActivityItem[]) {
    recentActivity.value = items
  }

  function reset() {
    sessionUserId.value = null
    workflowCount.value = 0
    parentUnreadCount.value = 0
    recentActivity.value = []
  }

  const badgeCount = computed(() =>
    Math.max(workflowCount.value, parentUnreadCount.value),
  )

  return {
    sessionUserId,
    workflowCount,
    parentUnreadCount,
    recentActivity,
    badgeCount,
    notify,
    bindSession,
    setWorkflowCount,
    setParentUnreadCount,
    decrementParentUnread,
    setRecentActivity,
    reset,
  }
})
