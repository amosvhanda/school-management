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
import { formatChartDay } from '@/lib/format'
import type { ActivityPoint } from '@/types/dashboard'

const props = defineProps<{ data: ActivityPoint[] }>()

const chartConfig = {
  value: { label: 'Activity', color: 'var(--chart-1)' },
} satisfies ChartConfig

const chartData = computed(() =>
  props.data.map((point, index) => ({
    ...point,
    index,
    label: point.label ?? formatChartDay(point.date),
  })),
)

const hasData = computed(() => chartData.value.some((d) => Number(d.value) > 0))

const tooltip = computed(() =>
  componentToString(chartConfig, ChartTooltipContent, {
    labelFormatter: (x) => chartData.value[Number(x)]?.label ?? String(x),
  }),
)
</script>

<template>
  <Card>
    <CardHeader class="border-b border-border/60 px-5 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">Activity trend</CardTitle>
      <CardDescription>Daily attendance records and payments (last 30 days)</CardDescription>
    </CardHeader>
    <CardContent class="px-5 pt-5">
      <EmptyState
        v-if="!chartData.length || !hasData"
        class="h-[280px] justify-center"
        title="No activity yet"
        description="Attendance and payment activity will appear here once recorded."
      />
      <ChartContainer v-else :config="chartConfig" class="aspect-auto h-[280px] w-full" cursor>
        <VisXYContainer :data="chartData" :margin="{ left: 8, right: 12, top: 12, bottom: 28 }">
          <VisArea
            :x="(d: { index: number }) => d.index"
            :y="(d: { value: number }) => d.value"
            color="var(--chart-1)"
            :opacity="0.15"
          />
          <VisLine
            :x="(d: { index: number }) => d.index"
            :y="(d: { value: number }) => d.value"
            color="var(--chart-1)"
            :line-width="2.5"
          />
          <ChartCrosshair
            color="var(--chart-1)"
            :template="tooltip"
          />
          <VisAxis
            type="x"
            :x="(d: { index: number }) => d.index"
            :tick-format="(i: number) => chartData[i]?.label ?? ''"
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
