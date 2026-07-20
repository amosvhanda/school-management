<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent } from '@/components/ui/card'

defineProps<{
  label?: string
}>()
</script>

<template>
  <div
    class="space-y-6"
    role="status"
    aria-live="polite"
    aria-busy="true"
    :aria-label="label ?? 'Loading page content'"
  >
    <span class="sr-only">{{ label ?? 'Loading page content' }}…</span>
    <div class="space-y-2">
      <Skeleton class="h-8 w-56" />
      <Skeleton class="h-4 w-80 max-w-full" />
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <Card v-for="i in 3" :key="i" class="py-0">
        <CardContent class="space-y-3 px-5 py-5">
          <Skeleton class="h-3 w-24" />
          <Skeleton class="h-8 w-20" />
          <Skeleton class="h-3 w-32" />
        </CardContent>
      </Card>
    </div>
    <Card class="py-0">
      <CardContent class="px-5 py-5">
        <Skeleton class="h-64 w-full rounded-lg" />
      </CardContent>
    </Card>
  </div>
</template>
