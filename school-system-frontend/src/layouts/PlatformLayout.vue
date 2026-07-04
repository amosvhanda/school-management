<script setup lang="ts">
import DashboardShell from '@/components/app/DashboardShell.vue'
import { platformNavigation } from '@/lib/navigation'
</script>

<template>
  <DashboardShell :navigation="platformNavigation" title="Super Admin Portal" />
</template>
