<script setup lang="ts">
import { computed } from 'vue'
import { VisArea, VisAxis, VisLine, VisXYContainer } from '@unovis/vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { ChartContainer } from '@/components/ui/chart'
import type { ChartConfig } from '@/components/ui/chart'
import { formatChartDay } from '@/lib/format'
import type { ActivityPoint } from '@/types/dashboard'

const props = defineProps<{ data: ActivityPoint[] }>()

const chartConfig = {
  value: { label: 'Activity', color: 'hsl(var(--chart-1))' },
} satisfies ChartConfig

const chartData = computed(() =>
  props.data.map((point, index) => ({
    ...point,
    index,
    label: point.label ?? formatChartDay(point.date),
  })),
)
</script>

<template>
  <Card class="bg-card text-card-foreground shadow-sm">
    <CardHeader class="border-b border-border/60 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">Activity trend</CardTitle>
      <CardDescription class="text-xs">Daily attendance records and payments (last 30 days)</CardDescription>
    </CardHeader>
    <CardContent class="pt-6">
      <ChartContainer :config="chartConfig" class="aspect-auto h-[280px] w-full">
        <VisXYContainer :data="chartData" :margin="{ left: 8, right: 8, top: 8, bottom: 28 }">
          <VisArea
            :x="(d: { index: number }) => d.index"
            :y="(d: { value: number }) => d.value"
            color="hsl(var(--chart-1))"
            :opacity="0.12"
          />
          <VisLine
            :x="(d: { index: number }) => d.index"
            :y="(d: { value: number }) => d.value"
            color="hsl(var(--chart-1))"
            :line-width="2.5"
          />
          <VisAxis
            type="x"
            :x="(d: { index: number }) => d.index"
            :tick-format="(i: number) => chartData[i]?.label ?? ''"
            :num-ticks="6"
            :grid-line="false"
          />
          <VisAxis type="y" :num-ticks="4" />
        </VisXYContainer>
      </ChartContainer>
    </CardContent>
  </Card>
</template>
