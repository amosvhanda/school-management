<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import type { CommandCenterData } from '@/types/dashboard'

const props = defineProps<{ items: CommandCenterData['academic_heatmap'] }>()

const sorted = computed(() =>
  [...props.items].sort((a, b) => b.average_percent - a.average_percent),
)
</script>

<template>
  <Card>
    <CardHeader class="border-b border-border/60 px-5 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">Subject performance</CardTitle>
      <CardDescription>Average exam scores by subject</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4 px-5 pt-5">
      <div v-for="item in sorted" :key="item.subject_id" class="space-y-2">
        <div class="flex justify-between text-sm">
          <span class="font-medium text-muted-foreground">Subject #{{ item.subject_id }}</span>
          <span class="font-semibold tabular-nums text-foreground">{{ item.average_percent }}%</span>
        </div>
        <Progress :model-value="item.average_percent" class="h-2.5" :aria-label="`Subject ${item.subject_id} average`" />
      </div>
    </CardContent>
  </Card>
</template>
