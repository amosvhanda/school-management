<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { DashboardKpis } from '@/types/dashboard'
import { Card, CardContent } from '@/components/ui/card'
import DashboardSection from './DashboardSection.vue'

defineProps<{ kpis: DashboardKpis }>()
</script>

<template>
  <section class="space-y-4" aria-label="Secondary metrics">
    <DashboardSection
      title="Campus snapshot"
      description="Supporting figures across enrolment and operations"
      heading="h3"
    />
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
      <Card size="sm">
        <CardContent class="px-4 py-3">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Classes</p>
          <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums">{{ kpis.totalClasses }}</p>
        </CardContent>
      </Card>
      <Card size="sm">
        <CardContent class="px-4 py-3">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">System users</p>
          <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums">{{ kpis.totalUsers }}</p>
        </CardContent>
      </Card>
      <Card size="sm">
        <CardContent class="px-4 py-3">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Parents</p>
          <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums">{{ kpis.totalParents }}</p>
        </CardContent>
      </Card>
      <Card size="sm">
        <CardContent class="px-4 py-3">
          <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Monthly activity</p>
          <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums">{{ kpis.totalActivity }}</p>
        </CardContent>
      </Card>
      <RouterLink
        to="/enrollment"
        class="rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <Card size="sm" class="h-full transition-shadow hover:shadow-md">
          <CardContent class="px-4 py-3">
            <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Pending enrollments</p>
            <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums">{{ kpis.pendingEnrollments ?? 0 }}</p>
          </CardContent>
        </Card>
      </RouterLink>
      <RouterLink
        to="/hr/leave"
        class="rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <Card size="sm" class="h-full transition-shadow hover:shadow-md">
          <CardContent class="px-4 py-3">
            <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Pending leave</p>
            <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums">{{ kpis.pendingLeaveRequests ?? 0 }}</p>
          </CardContent>
        </Card>
      </RouterLink>
    </div>
  </section>
</template>
