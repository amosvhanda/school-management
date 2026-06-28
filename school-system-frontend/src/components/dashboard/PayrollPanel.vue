<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import type { PayrollSummary } from '@/types/dashboard'

defineProps<{ summary: PayrollSummary }>()

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}
</script>

<template>
  <Card class="surface-card border-0 shadow-sm">
    <CardHeader class="border-b border-border/60 pb-4">
      <CardTitle class="text-base font-semibold">Payroll this month</CardTitle>
      <CardDescription>Gross payroll and payment status</CardDescription>
    </CardHeader>
    <CardContent class="space-y-5">
      <div>
        <div class="mb-2 flex justify-between text-sm">
          <span class="text-muted-foreground">Paid</span>
          <span class="font-medium">${{ formatMoney(summary.total_paid) }} / ${{ formatMoney(summary.total_payroll) }}</span>
        </div>
        <Progress :model-value="summary.total_payroll ? (summary.total_paid / summary.total_payroll) * 100 : 0" />
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg border p-3">
          <p class="text-xs text-muted-foreground">Gross</p>
          <p class="text-lg font-semibold">${{ formatMoney(summary.total_payroll) }}</p>
        </div>
        <div class="rounded-lg border p-3">
          <p class="text-xs text-muted-foreground">Pending</p>
          <p class="text-lg font-semibold text-amber-600">${{ formatMoney(summary.total_pending) }}</p>
        </div>
        <div class="rounded-lg border p-3">
          <p class="text-xs text-muted-foreground">Partial</p>
          <p class="text-lg font-semibold">${{ formatMoney(summary.total_partial) }}</p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
