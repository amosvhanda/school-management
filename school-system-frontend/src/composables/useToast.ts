import { useNotificationStore } from '@/stores/notification.store'

export function useToast() {
  const notificationStore = useNotificationStore()

  return {
    success: (title: string, description?: string) =>
      notificationStore.notify({ title, description, variant: 'success' }),
    error: (title: string, description?: string) =>
      notificationStore.notify({ title, description, variant: 'destructive' }),
    info: (title: string, description?: string) =>
      notificationStore.notify({ title, description }),
    warning: (title: string, description?: string) =>
      notificationStore.notify({ title, description, variant: 'warning' }),
  }
}
