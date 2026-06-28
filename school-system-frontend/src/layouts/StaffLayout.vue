<script setup lang="ts">
import DashboardShell from '@/components/app/DashboardShell.vue'
import { staffNavigation } from '@/lib/navigation'
</script>

<template>
  <DashboardShell
    :navigation="staffNavigation"
    title="School ERP"
    show-license-banner
  />
</template>
