<script setup lang="ts">
import { computed } from 'vue'
import { VisAxis, VisGroupedBar, VisXYContainer } from '@unovis/vue'
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
import type { SchoolDashboardFeeRevenuePoint } from '@/types/dashboard'

const props = defineProps<{ data: SchoolDashboardFeeRevenuePoint[] }>()

const chartConfig = {
  total_fee: { label: 'Total fee', color: 'var(--chart-1)' },
  collected: { label: 'Collected', color: 'var(--chart-2)' },
} satisfies ChartConfig

const chartData = computed(() =>
  props.data.map((point, index) => ({
    ...point,
    index,
    total_fee: Number(point.total_fee) || 0,
    collected: Number(point.collected) || 0,
  })),
)

const hasData = computed(() =>
  chartData.value.some((row) => row.total_fee > 0 || row.collected > 0),
)

const totals = computed(() => ({
  totalFee: chartData.value.reduce((sum, row) => sum + row.total_fee, 0),
  collected: chartData.value.reduce((sum, row) => sum + row.collected, 0),
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
      <CardTitle class="text-base font-semibold tracking-tight">Revenue statistic</CardTitle>
      <CardDescription>Monthly billed fees vs collected payments</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4 px-5 pt-5">
      <dl class="grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-lg border border-border/70 bg-muted/20 p-3">
          <dt class="text-xs text-muted-foreground">Total fee (12 mo)</dt>
          <dd class="mt-1 font-semibold tabular-nums">{{ formatMoney(totals.totalFee) }}</dd>
        </div>
        <div class="rounded-lg border border-border/70 bg-muted/20 p-3">
          <dt class="text-xs text-muted-foreground">Collected (12 mo)</dt>
          <dd class="mt-1 font-semibold tabular-nums text-chart-2">{{ formatMoney(totals.collected) }}</dd>
        </div>
      </dl>

      <EmptyState
        v-if="!chartData.length || !hasData"
        class="h-[240px] justify-center"
        title="No fee activity yet"
        description="Invoice and payment totals will appear here as fees are billed and collected."
      />
      <ChartContainer v-else :config="chartConfig" class="aspect-auto h-[240px] w-full" cursor>
        <VisXYContainer :data="chartData" :margin="{ left: 8, right: 12, top: 12, bottom: 28 }">
          <VisGroupedBar
            :x="(d: { index: number }) => d.index"
            :y="[
              (d: { total_fee: number }) => d.total_fee,
              (d: { collected: number }) => d.collected,
            ]"
            :color="['var(--chart-1)', 'var(--chart-2)']"
            :rounded-corners="4"
            :bar-padding="0.16"
            :group-padding="0.28"
          />
          <ChartCrosshair color="var(--chart-1)" :template="tooltip" />
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
