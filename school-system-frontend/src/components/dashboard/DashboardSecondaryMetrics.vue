<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { DashboardKpis } from '@/types/dashboard'

defineProps<{ kpis: DashboardKpis }>()
</script>

<template>
  <section
    class="grid gap-px overflow-hidden rounded-xl border bg-muted shadow-sm sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
    aria-label="Secondary metrics"
  >
    <div class="bg-background px-5 py-4">
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Classes</p>
      <p class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums">{{ kpis.totalClasses }}</p>
    </div>
    <div class="bg-background px-5 py-4">
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">System users</p>
      <p class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums">{{ kpis.totalUsers }}</p>
    </div>
    <div class="bg-background px-5 py-4">
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Parents registered</p>
      <p class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums">{{ kpis.totalParents }}</p>
    </div>
    <div class="bg-background px-5 py-4">
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Monthly transactions</p>
      <p class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums">{{ kpis.totalActivity }}</p>
    </div>
    <RouterLink
      to="/enrollment"
      class="bg-background px-5 py-4 transition-colors hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
    >
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Pending enrollments</p>
      <p class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums">{{ kpis.pendingEnrollments ?? 0 }}</p>
    </RouterLink>
    <RouterLink
      to="/hr/leave"
      class="bg-background px-5 py-4 transition-colors hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
    >
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Pending leave</p>
      <p class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums">{{ kpis.pendingLeaveRequests ?? 0 }}</p>
    </RouterLink>
  </section>
</template>
