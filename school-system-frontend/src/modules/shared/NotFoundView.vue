<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { FileQuestion } from '@lucide/vue'
import { Button } from '@/components/ui/button'
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-5 py-24 text-center">
    <div
      class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/15"
      aria-hidden="true"
    >
      <FileQuestion class="size-7" />
    </div>
    <div class="space-y-2">
      <h1 class="font-heading text-2xl font-semibold tracking-tight">Page not found</h1>
      <p class="max-w-sm text-base text-muted-foreground">
        The page you requested does not exist or you may not have access to it.
      </p>
    </div>
    <Button as-child class="rounded-xl">
      <RouterLink to="/">Back to dashboard</RouterLink>
    </Button>
  </div>
</template>
