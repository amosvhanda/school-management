<script setup lang="ts">
import { computed } from 'vue'
import { VisArea, VisAxis, VisLine, VisXYContainer } from '@unovis/vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  ChartContainer,
  ChartCrosshair,
  ChartLegendContent,
  ChartTooltipContent,
  componentToString,
  type ChartConfig,
} from '@/components/ui/chart'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { formatMoney } from '@/lib/finance-constants'
import type { SchoolDashboardIncomeExpensePoint } from '@/types/dashboard'

const props = defineProps<{ data: SchoolDashboardIncomeExpensePoint[] }>()

const chartConfig = {
  income: { label: 'Income', color: 'var(--chart-2)' },
  expense: { label: 'Expense', color: 'var(--chart-5)' },
} satisfies ChartConfig

const chartData = computed(() =>
  props.data.map((point, index) => ({
    ...point,
    index,
    income: Number(point.income) || 0,
    expense: Number(point.expense) || 0,
  })),
)

const hasData = computed(() =>
  chartData.value.some((row) => row.income > 0 || row.expense > 0),
)

const totals = computed(() => ({
  income: chartData.value.reduce((sum, row) => sum + row.income, 0),
  expense: chartData.value.reduce((sum, row) => sum + row.expense, 0),
}))

const tooltip = computed(() =>
  componentToString(chartConfig, ChartTooltipContent, {
    labelFormatter: (x) => String(chartData.value[Number(x)]?.month ?? x),
  }),
)
</script>

<template>
  <Card class="h-full">
    <CardHeader class="border-b border-border/60 px-5 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">Income vs expense</CardTitle>
      <CardDescription>Monthly ledger income and expense trends</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4 px-5 pt-5">
      <dl class="grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-lg border border-border/70 bg-muted/20 p-3">
          <dt class="text-xs text-muted-foreground">Income (12 mo)</dt>
          <dd class="mt-1 font-semibold tabular-nums text-chart-2">{{ formatMoney(totals.income) }}</dd>
        </div>
        <div class="rounded-lg border border-border/70 bg-muted/20 p-3">
          <dt class="text-xs text-muted-foreground">Expense (12 mo)</dt>
          <dd class="mt-1 font-semibold tabular-nums text-destructive">{{ formatMoney(totals.expense) }}</dd>
        </div>
      </dl>

      <EmptyState
        v-if="!chartData.length || !hasData"
        class="h-[240px] justify-center"
        title="No ledger activity yet"
        description="Income and expense trends will appear once account transactions are recorded."
      />
      <ChartContainer v-else :config="chartConfig" class="aspect-auto h-[240px] w-full" cursor>
        <VisXYContainer :data="chartData" :margin="{ left: 8, right: 12, top: 12, bottom: 28 }">
          <VisArea
            :x="(d: { index: number }) => d.index"
            :y="(d: { income: number }) => d.income"
            color="var(--chart-2)"
            :opacity="0.18"
          />
          <VisArea
            :x="(d: { index: number }) => d.index"
            :y="(d: { expense: number }) => d.expense"
            color="var(--chart-5)"
            :opacity="0.12"
          />
          <VisLine
            :x="(d: { index: number }) => d.index"
            :y="(d: { income: number }) => d.income"
            color="var(--chart-2)"
            :line-width="2.5"
          />
          <VisLine
            :x="(d: { index: number }) => d.index"
            :y="(d: { expense: number }) => d.expense"
            color="var(--chart-5)"
            :line-width="2.5"
          />
          <ChartCrosshair color="var(--chart-2)" :template="tooltip" />
          <VisAxis
            type="x"
            :x="(d: { index: number }) => d.index"
            :tick-format="(i: number) => String(chartData[i]?.month ?? '')"
            :num-ticks="6"
            :grid-line="false"
            :tick-line="false"
            :domain-line="false"
          />
          <VisAxis type="y" :num-ticks="4" :tick-line="false" :domain-line="false" />
        </VisXYContainer>
        <ChartLegendContent />
      </ChartContainer>
    </CardContent>
  </Card>
</template>
