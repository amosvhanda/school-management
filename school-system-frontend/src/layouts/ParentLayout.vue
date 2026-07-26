<script setup lang="ts">
import DashboardShell from '@/components/app/DashboardShell.vue'
import { brandName } from '@/lib/brand'
import { parentNavigation } from '@/lib/navigation'
</script>

<template>
  <DashboardShell :navigation="parentNavigation" :title="brandName" />
</template>
