<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton'
</script>

<template>
  <div class="space-y-8" aria-busy="true" aria-label="Loading dashboard">
    <Skeleton class="h-36 w-full rounded-2xl" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Skeleton v-for="i in 4" :key="i" class="h-32 rounded-xl" />
    </div>

    <Skeleton class="h-20 w-full rounded-xl" />

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
      <Skeleton v-for="i in 6" :key="i" class="h-28 rounded-xl" />
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
      <div class="space-y-4 xl:col-span-2">
        <Skeleton class="h-[340px] rounded-xl" />
        <Skeleton class="h-[340px] rounded-xl" />
      </div>
      <Skeleton class="h-[420px] rounded-xl" />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <Skeleton class="h-64 rounded-xl" />
      <Skeleton class="h-64 rounded-xl" />
    </div>
  </div>
</template>
