<script setup lang="ts">
import { computed } from 'vue'
import { VisDonut, VisDonutSelectors, VisSingleContainer } from '@unovis/vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  componentToString,
  type ChartConfig,
} from '@/components/ui/chart'
import EmptyState from '@/components/feedback/EmptyState.vue'
import type { SchoolDashboardAdmissionSlice } from '@/types/dashboard'

const COLORS = [
  'var(--chart-1)',
  'var(--chart-2)',
  'var(--chart-3)',
  'var(--chart-4)',
  'var(--chart-5)',
  'var(--primary)',
  'var(--muted-foreground)',
  'var(--destructive)',
]

const props = defineProps<{ data: SchoolDashboardAdmissionSlice[] }>()

const slices = computed(() =>
  props.data
    .map((row) => ({
      label: row.label || 'Unassigned',
      value: Number(row.value) || 0,
    }))
    .filter((row) => row.value > 0),
)

const total = computed(() => slices.value.reduce((sum, row) => sum + row.value, 0))

const chartConfig = computed(() => {
  const config: ChartConfig = {}
  slices.value.forEach((slice, index) => {
    config[`slice_${index}`] = {
      label: slice.label,
      color: COLORS[index % COLORS.length],
    }
  })
  return config
})

const colorAccessor = (_d: SchoolDashboardAdmissionSlice, i: number) => COLORS[i % COLORS.length]

const tooltip = computed(() =>
  componentToString(chartConfig.value, ChartTooltipContent, {
    labelKey: 'label',
  }),
)
</script>

<template>
  <Card class="h-full">
    <CardHeader class="border-b border-border/60 px-5 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">New admissions</CardTitle>
      <CardDescription>Admissions this year by class</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4 px-5 pt-5">
      <EmptyState
        v-if="!slices.length"
        class="h-[260px] justify-center"
        title="No admissions yet"
        description="New students enrolled this year will appear in this breakdown."
      />
      <template v-else>
        <ChartContainer :config="chartConfig" class="relative mx-auto aspect-square h-[200px] w-full max-w-[220px]">
          <VisSingleContainer :data="slices" :margin="{ top: 8, bottom: 8, left: 8, right: 8 }">
            <VisDonut
              :value="(d: SchoolDashboardAdmissionSlice) => d.value"
              :color="colorAccessor"
              :arc-width="28"
              :pad-angle="0.02"
              :corner-radius="3"
              :show-background="false"
            />
            <ChartTooltip :triggers="{ [VisDonutSelectors.segment]: tooltip }" />
          </VisSingleContainer>
          <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
            <p class="text-2xl font-semibold tabular-nums tracking-tight">{{ total }}</p>
            <p class="text-xs text-muted-foreground">Total</p>
          </div>
        </ChartContainer>

        <ul class="space-y-2" aria-label="Admissions by class">
          <li
            v-for="(slice, index) in slices"
            :key="slice.label"
            class="flex items-center justify-between gap-3 text-sm"
          >
            <span class="flex min-w-0 items-center gap-2 text-muted-foreground">
              <span
                class="size-2.5 shrink-0 rounded-full"
                :style="{ background: COLORS[index % COLORS.length] }"
                aria-hidden="true"
              />
              <span class="truncate">{{ slice.label }}</span>
            </span>
            <span class="font-medium tabular-nums">{{ slice.value }}</span>
          </li>
        </ul>
      </template>
    </CardContent>
  </Card>
</template>
