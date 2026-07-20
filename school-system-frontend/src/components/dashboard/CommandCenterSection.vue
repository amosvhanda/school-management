<script setup lang="ts">
import { computed } from 'vue'
import { AlertTriangle, CheckCircle2, Clock } from '@lucide/vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import EmptyState from '@/components/feedback/EmptyState.vue'
import type { CommandCenterData } from '@/types/dashboard'

const props = defineProps<{ data: CommandCenterData }>()

const topAlerts = computed(() => props.data.risk_alerts.slice(0, 6))

function alertLabel(alert: Record<string, unknown>) {
  const type = String(alert.type ?? '')
  if (type === 'unpaid_fees') return 'Unpaid fees'
  if (type === 'dropout_risk') return 'Dropout risk'
  return type || 'Alert'
}

function alertDetail(alert: Record<string, unknown>) {
  const type = String(alert.type ?? '')
  if (type === 'unpaid_fees') return `Balance: $${Number(alert.balance ?? 0).toLocaleString()}`
  if (alert.risk_level) return `Risk: ${String(alert.risk_level)} · Score ${alert.dropout_risk_score ?? '—'}`
  return ''
}
</script>

<template>
  <div class="grid gap-4 lg:grid-cols-3">
    <Card class="lg:col-span-2">
      <CardHeader class="border-b border-border/60 px-5 pb-4">
        <CardTitle class="flex items-center gap-2 text-base font-semibold tracking-tight">
          <AlertTriangle class="size-4 text-amber-500" aria-hidden="true" />
          Risk alerts
        </CardTitle>
        <CardDescription>Students requiring attention</CardDescription>
      </CardHeader>
      <CardContent class="p-0">
        <EmptyState v-if="!topAlerts.length" title="No active alerts" description="All students look good for now." class="py-12" />
        <Table v-else>
          <TableHeader>
            <TableRow>
              <TableHead class="text-xs">Student</TableHead>
              <TableHead class="text-xs">Type</TableHead>
              <TableHead class="text-xs">Details</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(alert, i) in topAlerts" :key="i" class="transition-colors">
              <TableCell class="font-medium text-sm text-foreground">{{ alert.student_name ?? '—' }}</TableCell>
              <TableCell>
                <Badge variant="outline" class="font-normal text-xs capitalize">{{ alertLabel(alert) }}</Badge>
              </TableCell>
              <TableCell class="text-muted-foreground text-sm">{{ alertDetail(alert) }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>

    <div class="space-y-4">
      <Card>
        <CardHeader class="border-b border-border/60 px-5 pb-3">
          <CardTitle class="text-sm font-semibold tracking-wide text-muted-foreground uppercase">School health</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3 px-5 pt-4 text-sm">
          <div class="flex items-center justify-between border-b border-border/50 pb-2">
            <span class="text-muted-foreground">Active students</span>
            <span class="font-semibold tabular-nums text-foreground">{{ data.school_health.active_students }}</span>
          </div>
          <div class="flex items-center justify-between border-b border-border/50 pb-2">
            <span class="text-muted-foreground">Avg performance</span>
            <span class="font-semibold tabular-nums text-foreground">{{ data.school_health.average_performance }}%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">Attendance anomalies</span>
            <span class="font-semibold tabular-nums text-amber-600 dark:text-amber-500">{{ data.school_health.attendance_anomalies }}</span>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="border-b border-border/60 px-5 pb-3">
          <CardTitle class="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Scorecard</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3 px-5 pt-4 text-sm">
          <div class="flex items-center justify-between border-b border-border/50 pb-2">
            <span class="text-muted-foreground">Enrollment</span>
            <span class="font-semibold tabular-nums text-foreground">{{ data.kpi_scorecard.enrollment }}</span>
          </div>
          <div class="flex items-center justify-between border-b border-border/50 pb-2">
            <span class="text-muted-foreground">Fee collection</span>
            <span class="font-semibold tabular-nums text-foreground">{{ data.kpi_scorecard.fee_collection_rate }}%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">Pending leave</span>
            <span class="flex items-center gap-1 font-semibold tabular-nums text-foreground">
              <Clock class="size-3.5 text-muted-foreground" aria-hidden="true" />
              {{ data.kpi_scorecard.pending_leave }}
            </span>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="border-b border-border/60 px-5 pb-3">
          <CardTitle class="flex items-center gap-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
            <CheckCircle2 class="size-4 text-emerald-500" aria-hidden="true" />
            Approvals
          </CardTitle>
        </CardHeader>
        <CardContent class="px-5 pt-4">
          <p class="text-3xl font-bold tracking-tight text-foreground">{{ data.approval_queue.length }}</p>
          <p class="text-xs text-muted-foreground mt-0.5">Pending workflow items</p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
