<script setup lang="ts">
import PageLoader from '@/components/feedback/PageLoader.vue'
import { useAuthStore } from '@/stores/auth.store'

const authStore = useAuthStore()
</script>

<template>
  <PageLoader v-if="!authStore.initialized || authStore.loading" label="Loading your workspace" />
  <slot v-else />
</template>
