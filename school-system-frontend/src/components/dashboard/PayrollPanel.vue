<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Separator } from '@/components/ui/separator'
import type { PayrollSummary } from '@/types/dashboard'

defineProps<{ summary: PayrollSummary }>()

function formatMoney(value: number) {
  return value.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

function paidPercent(summary: PayrollSummary) {
  if (!summary.total_payroll) return 0
  return Math.min(100, (summary.total_paid / summary.total_payroll) * 100)
}
</script>

<template>
  <Card class="h-full">
    <CardHeader class="border-b border-border/60 px-5 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">Payroll this month</CardTitle>
      <CardDescription>Gross payroll and payment status</CardDescription>
    </CardHeader>
    <CardContent class="space-y-5 px-5 pt-5">
      <div class="space-y-2">
        <div class="flex justify-between text-sm">
          <span class="text-muted-foreground">Paid</span>
          <span class="font-medium tabular-nums">
            ${{ formatMoney(summary.total_paid) }}
            <span class="text-muted-foreground"> / ${{ formatMoney(summary.total_payroll) }}</span>
          </span>
        </div>
        <Progress :model-value="paidPercent(summary)" class="h-2.5" aria-label="Payroll paid progress" />
      </div>

      <Separator />

      <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg border border-border/70 bg-muted/20 p-3">
          <p class="text-xs text-muted-foreground">Gross</p>
          <p class="mt-1 text-lg font-semibold tabular-nums">${{ formatMoney(summary.total_payroll) }}</p>
        </div>
        <div class="rounded-lg border border-border/70 bg-muted/20 p-3">
          <p class="text-xs text-muted-foreground">Pending</p>
          <p class="mt-1 text-lg font-semibold tabular-nums text-amber-600 dark:text-amber-400">
            ${{ formatMoney(summary.total_pending) }}
          </p>
        </div>
        <div class="rounded-lg border border-border/70 bg-muted/20 p-3">
          <p class="text-xs text-muted-foreground">Partial</p>
          <p class="mt-1 text-lg font-semibold tabular-nums">${{ formatMoney(summary.total_partial) }}</p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
