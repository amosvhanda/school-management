import { ref, computed } from 'vue'

export interface ToastOptions {
  id?: string
  title?: string
  description?: string
  variant?: 'default' | 'destructive'
  duration?: number
}

const toasts = ref<ToastOptions[]>([])

export function useToast() {
  const addToast = (toast: ToastOptions) => {
    const id = toast.id || Math.random().toString(36).substring(2, 9)
    const newToast = { ...toast, id, duration: toast.duration ?? 3000 }

    toasts.value.push(newToast)

    if (newToast.duration > 0) {
      setTimeout(() => {
        dismiss(id)
      }, newToast.duration)
    }
  }

  const dismiss = (id: string) => {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  // Support both object arguments and direct text triggers
  const toast = (options: ToastOptions) => {
    addToast(options)
  }

  return {
    toasts: computed(() => toasts.value),
    toast,
    dismiss,
  }
}
