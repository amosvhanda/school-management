<script setup lang="ts">
import { computed } from 'vue'
import { VisAxis, VisGroupedBar, VisXYContainer } from '@unovis/vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { ChartContainer } from '@/components/ui/chart'
import type { ChartConfig } from '@/components/ui/chart'
import { formatMonth } from '@/lib/format'
import type { MonthlyStat } from '@/types/dashboard'

const props = defineProps<{ data: MonthlyStat[] }>()

const chartConfig = {
  Revenue: { label: 'Revenue', color: 'hsl(var(--chart-1))' },
  Users: { label: 'Users', color: 'hsl(var(--chart-2))' },
  Transactions: { label: 'Transactions', color: 'hsl(var(--chart-3))' },
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
</script>

<template>
  <Card class="surface-card border-0 shadow-sm">
    <CardHeader class="border-b border-border/60 pb-4">
      <CardTitle class="text-base font-semibold">Monthly overview</CardTitle>
      <CardDescription>Revenue, new users, and transactions by month</CardDescription>
    </CardHeader>
    <CardContent>
      <ChartContainer :config="chartConfig" class="aspect-auto h-[280px] w-full">
        <VisXYContainer :data="chartData" :margin="{ left: 8, right: 8, top: 8, bottom: 28 }">
          <VisGroupedBar
            :x="(d: { index: number }) => d.index"
            :y="[(d: Record<string, number>) => d.Revenue, (d: Record<string, number>) => d.Users, (d: Record<string, number>) => d.Transactions]"
            :color="['hsl(var(--chart-1))', 'hsl(var(--chart-2))', 'hsl(var(--chart-3))']"
            :rounded-corners="4"
            :bar-padding="0.15"
            :group-padding="0.2"
          />
          <VisAxis
            type="x"
            :x="(d: { index: number }) => d.index"
            :tick-format="(i: number) => String(chartData[i]?.month ?? '')"
            :num-ticks="6"
            :grid-line="false"
          />
          <VisAxis type="y" :num-ticks="4" />
        </VisXYContainer>
      </ChartContainer>
      <div class="mt-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
        <span v-for="(cfg, key) in chartConfig" :key="key" class="flex items-center gap-1.5">
          <span class="size-2 rounded-full" :style="{ background: cfg.color }" />
          {{ cfg.label }}
        </span>
      </div>
    </CardContent>
  </Card>
</template>
