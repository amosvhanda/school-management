<script setup lang="ts">
import DashboardShell from '@/components/app/DashboardShell.vue'
import { studentNavigation } from '@/lib/navigation'
</script>

<template>
  <DashboardShell :navigation="studentNavigation" title="Student Portal" />
</template>
