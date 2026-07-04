import { defineStore } from 'pinia'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import type { RecentActivityItem } from '@/types/dashboard'

export interface ToastPayload {
  title: string
  description?: string
  variant?: 'default' | 'destructive' | 'success'
}

export const useNotificationStore = defineStore('notification', () => {
  const workflowCount = ref(0)
  const recentActivity = ref<RecentActivityItem[]>([])

  function notify(payload: ToastPayload) {
    if (payload.variant === 'destructive') {
      toast.error(payload.title, { description: payload.description })
    } else if (payload.variant === 'success') {
      toast.success(payload.title, { description: payload.description })
    } else {
      toast(payload.title, { description: payload.description })
    }
  }

  function setWorkflowCount(count: number) {
    workflowCount.value = count
  }

  function setRecentActivity(items: RecentActivityItem[]) {
    recentActivity.value = items
  }

  function reset() {
    workflowCount.value = 0
    recentActivity.value = []
  }

  const unreadCount = () => workflowCount.value + (recentActivity.value.length > 0 ? 1 : 0)

  return {
    workflowCount,
    recentActivity,
    notify,
    setWorkflowCount,
    setRecentActivity,
    reset,
    unreadCount,
  }
})
