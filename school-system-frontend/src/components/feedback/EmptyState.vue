<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
}>()
</script>

<template>
  <div
    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed p-12 text-center"
    role="status"
  >
    <h3 class="text-lg font-semibold">{{ title ?? 'No records found' }}</h3>
    <p class="max-w-md text-sm text-muted-foreground">
      {{ description ?? 'There is nothing to show here yet.' }}
    </p>
    <slot />
  </div>
</template>
