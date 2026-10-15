<script setup lang="ts">
import { AlertCircle } from '@lucide/vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

defineProps<{
  title?: string
  description?: string
  class?: string
}>()

defineEmits<{ retry: [] }>()
</script>

<template>
  <Alert
    variant="destructive"
    :class="cn(
      'flex flex-col items-start gap-4 rounded-2xl border-destructive/25 bg-destructive/5 p-5 shadow-[var(--shadow-soft)] sm:flex-row sm:items-center sm:justify-between sm:p-6',
      $props.class,
    )"
    role="alert"
  >
    <div class="flex gap-3">
      <span
        class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-destructive/10 text-destructive"
        aria-hidden="true"
      >
        <AlertCircle class="size-4" />
      </span>
      <div class="space-y-1">
        <AlertTitle class="text-base font-semibold">
          {{ title ?? 'Something went wrong' }}
        </AlertTitle>
        <AlertDescription class="text-sm leading-relaxed text-destructive/90">
          {{ description ?? 'We could not load this page. Please try again.' }}
        </AlertDescription>
      </div>
    </div>
    <Button
      variant="outline"
      class="shrink-0 border-destructive/30 hover:bg-destructive/10"
      @click="$emit('retry')"
    >
      Try again
    </Button>
  </Alert>
</template>
