<script setup lang="ts">
import { onMounted, ref } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { fetchCommandCenter } from '@/services/dashboard.service'
import type { CommandCenterData } from '@/types/dashboard'

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<CommandCenterData | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    data.value = await fetchCommandCenter()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load command center'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Command center</h1>
      <p class="text-muted-foreground">Executive overview of school health, finance, and risk</p>
    </div>
    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else-if="data">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <KpiCard title="Active students" :value="data.school_health.active_students" />
        <KpiCard title="Attendance anomalies" :value="data.school_health.attendance_anomalies" />
        <KpiCard title="Avg performance" :value="`${data.school_health.average_performance}%`" />
        <KpiCard title="Fee collection rate" :value="`${data.kpi_scorecard.fee_collection_rate}%`" />
      </div>
      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Financial status</CardTitle></CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p>Outstanding: ${{ data.financial_status.outstanding_fees.toLocaleString() }}</p>
            <p>Collected this month: ${{ data.financial_status.collected_this_month.toLocaleString() }}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Operational inbox</CardTitle></CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p>{{ data.approval_queue.length }} pending approvals</p>
            <p>{{ data.risk_alerts.length }} risk alerts</p>
            <p>{{ data.kpi_scorecard.pending_leave }} pending leave requests</p>
          </CardContent>
        </Card>
      </div>
    </template>
  </div>
</template>
