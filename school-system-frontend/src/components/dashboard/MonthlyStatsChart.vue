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
import { formatMonth } from '@/lib/format'
import type { MonthlyStat } from '@/types/dashboard'

const props = defineProps<{ data: MonthlyStat[] }>()

const chartConfig = {
  Revenue: { label: 'Revenue', color: 'var(--chart-1)' },
  Users: { label: 'Users', color: 'var(--chart-2)' },
  Transactions: { label: 'Transactions', color: 'var(--chart-3)' },
} satisfies ChartConfig

const chartData = computed(() => {
  const months = [...new Set(props.data.map((d) => d.month))]
  return months.map((month, index) => {
    const row: Record<string, number | string> = { month: formatMonth(month), index }
    for (const cat of ['Revenue', 'Users', 'Transactions'] as const) {
      row[cat] = props.data.find((d) => d.month === month && d.category === cat)?.value ?? 0
    }
    return row
  })
})

const hasData = computed(() =>
  chartData.value.some((row) =>
    Number(row.Revenue) > 0 || Number(row.Users) > 0 || Number(row.Transactions) > 0,
  ),
)

const tooltip = computed(() =>
  componentToString(chartConfig, ChartTooltipContent, {
    labelFormatter: (x) => String(chartData.value[Number(x)]?.month ?? x),
  }),
)
</script>

<template>
  <Card>
    <CardHeader class="border-b border-border/60 px-5 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">Monthly overview</CardTitle>
      <CardDescription>Revenue, new users, and transactions by month</CardDescription>
    </CardHeader>
    <CardContent class="px-5 pt-5">
      <EmptyState
        v-if="!chartData.length || !hasData"
        class="h-[280px] justify-center"
        title="No monthly stats yet"
        description="Revenue and enrolment trends will show here as activity builds up."
      />
      <ChartContainer v-else :config="chartConfig" class="aspect-auto h-[280px] w-full" cursor>
        <VisXYContainer :data="chartData" :margin="{ left: 8, right: 12, top: 12, bottom: 28 }">
          <VisGroupedBar
            :x="(d: { index: number }) => d.index"
            :y="[
              (d: Record<string, number>) => d.Revenue,
              (d: Record<string, number>) => d.Users,
              (d: Record<string, number>) => d.Transactions,
            ]"
            :color="['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)']"
            :rounded-corners="6"
            :bar-padding="0.18"
            :group-padding="0.28"
          />
          <ChartCrosshair
            color="var(--chart-1)"
            :template="tooltip"
          />
          <VisAxis
            type="x"
            :x="(d: { index: number }) => d.index"
            :tick-format="(i: number) => String(chartData[i]?.month ?? '')"
            :num-ticks="6"
            :grid-line="false"
            :tick-line="false"
            :domain-line="false"
          />
          <VisAxis
            type="y"
            :num-ticks="4"
            :tick-line="false"
            :domain-line="false"
          />
        </VisXYContainer>
        <ChartLegendContent />
      </ChartContainer>
    </CardContent>
  </Card>
</template>
