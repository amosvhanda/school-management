<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton'

defineProps<{
  label?: string
}>()
</script>

<template>
  <div
    class="space-y-4 p-6"
    role="status"
    aria-live="polite"
    aria-busy="true"
    :aria-label="label ?? 'Loading page content'"
  >
    <span class="sr-only">{{ label ?? 'Loading page content' }}…</span>
    <Skeleton class="h-8 w-48" />
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Skeleton v-for="i in 4" :key="i" class="h-28 rounded-xl" />
    </div>
    <Skeleton class="h-64 rounded-xl" />
  </div>
</template>
