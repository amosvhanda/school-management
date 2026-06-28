<script setup lang="ts">
import DashboardShell from '@/components/app/DashboardShell.vue'
import { parentNavigation } from '@/lib/navigation'
</script>

<template>
  <DashboardShell :navigation="parentNavigation" title="Parent Portal" />
</template>
