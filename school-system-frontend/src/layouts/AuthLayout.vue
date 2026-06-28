<script setup lang="ts">
import { RouterView } from 'vue-router'
import ThemeToggle from '@/components/app/ThemeToggle.vue'
</script>

<template>
  <div class="relative flex min-h-svh flex-col bg-background">
    <div class="absolute top-4 right-4 z-10">
      <ThemeToggle />
    </div>
    <RouterView />
  </div>
</template>
