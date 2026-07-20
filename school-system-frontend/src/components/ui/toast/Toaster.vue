<script setup lang="ts">
import { useToast } from './use-toast'
import { X } from '@lucide/vue'

const { toasts, dismiss } = useToast()
</script>

<template>
  <div
    aria-live="assertive"
    class="pointer-events-none fixed bottom-0 right-0 z-50 flex max-h-screen w-full flex-col p-4 md:max-w-md gap-2"
  >
    <div
      v-for="toast in toasts"
      :key="toast.id"
      class="pointer-events-auto flex w-full items-center justify-between space-x-4 overflow-hidden rounded-xl border p-4 shadow-lg transition-all animate-in slide-in-from-bottom-5 duration-300"
      :class="[
        toast.variant === 'destructive'
          ? 'destructive border-destructive bg-destructive text-destructive-foreground'
          : 'border-border bg-card text-card-foreground'
      ]"
    >
      <div class="grid gap-1">
        <div v-if="toast.title" class="text-sm font-semibold leading-none">
          {{ toast.title }}
        </div>
        <div v-if="toast.description" class="text-xs opacity-90 leading-normal">
          {{ toast.description }}
        </div>
      </div>

      <button
        type="button"
        class="rounded-md p-1 opacity-50 transition-opacity hover:opacity-100 focus:outline-none"
        @click="dismiss(toast.id!)"
      >
        <X class="size-4" />
        <span class="sr-only">Close</span>
      </button>
    </div>
  </div>
</template>
