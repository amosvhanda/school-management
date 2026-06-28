<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Button } from '@/components/ui/button'
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-4 py-24 text-center">
    <h1 class="text-2xl font-semibold">Page not found</h1>
    <p class="text-muted-foreground">The page you requested does not exist.</p>
    <Button as-child><RouterLink to="/">Back to dashboard</RouterLink></Button>
  </div>
</template>
