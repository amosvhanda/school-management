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
    :class="cn('flex flex-col items-start gap-4 border-destructive/30 bg-destructive/5 p-6 sm:flex-row sm:items-center sm:justify-between', $props.class)"
    role="alert"
  >
    <div class="flex gap-3">
      <AlertCircle class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
      <div class="space-y-1">
        <AlertTitle class="text-base font-semibold">
          {{ title ?? 'Something went wrong' }}
        </AlertTitle>
        <AlertDescription class="text-sm text-destructive/90">
          {{ description ?? 'We could not load this page. Please try again.' }}
        </AlertDescription>
      </div>
    </div>
    <Button variant="outline" class="shrink-0 border-destructive/30" @click="$emit('retry')">
      Try again
    </Button>
  </Alert>
</template>
