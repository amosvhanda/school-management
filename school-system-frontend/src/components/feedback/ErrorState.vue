<script setup lang="ts">
import { Button } from '@/components/ui/button'

defineProps<{
  title?: string
  description?: string
}>()

defineEmits<{ retry: [] }>()
</script>

<template>
  <div
    class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-12 text-center"
    role="alert"
  >
    <h3 class="text-lg font-semibold">{{ title ?? 'Something went wrong' }}</h3>
    <p class="max-w-md text-sm text-muted-foreground">
      {{ description ?? 'We could not load this page. Please try again.' }}
    </p>
    <Button variant="outline" @click="$emit('retry')">Try again</Button>
  </div>
</template>
