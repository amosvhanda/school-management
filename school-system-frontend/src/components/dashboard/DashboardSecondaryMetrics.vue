<script setup lang="ts">
import type { DashboardKpis } from '@/types/dashboard'

defineProps<{ kpis: DashboardKpis }>()
</script>

<template>
  <section
    class="grid gap-px overflow-hidden rounded-xl border bg-muted shadow-sm sm:grid-cols-2 lg:grid-cols-4"
    aria-label="Secondary metrics"
  >
    <div class="bg-background px-5 py-4">
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Classes</p>
      <p class="text-2xl font-bold tracking-tight text-foreground tabular-nums mt-1">{{ kpis.totalClasses }}</p>
    </div>
    <div class="bg-background px-5 py-4">
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">System users</p>
      <p class="text-2xl font-bold tracking-tight text-foreground tabular-nums mt-1">{{ kpis.totalUsers }}</p>
    </div>
    <div class="bg-background px-5 py-4">
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Parents registered</p>
      <p class="text-2xl font-bold tracking-tight text-foreground tabular-nums mt-1">{{ kpis.totalParents }}</p>
    </div>
    <div class="bg-background px-5 py-4">
      <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Monthly transactions</p>
      <p class="text-2xl font-bold tracking-tight text-foreground tabular-nums mt-1">{{ kpis.totalActivity }}</p>
    </div>
  </section>
</template>
