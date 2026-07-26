<script setup lang="ts">
import DashboardShell from '@/components/app/DashboardShell.vue'
import { brandName } from '@/lib/brand'
import { studentNavigation } from '@/lib/navigation'
</script>

<template>
  <DashboardShell :navigation="studentNavigation" :title="brandName" />
</template>
