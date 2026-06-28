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
  <Card class="bg-card text-card-foreground shadow-sm">
    <CardHeader>
      <CardTitle class="text-base font-semibold tracking-tight">Subject performance</CardTitle>
      <CardDescription class="text-xs">Average exam scores by subject</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <div v-for="item in sorted" :key="item.subject_id" class="space-y-2">
        <div class="flex justify-between text-sm">
          <span class="text-muted-foreground font-medium">Subject #{{ item.subject_id }}</span>
          <span class="font-semibold text-foreground">{{ item.average_percent }}%</span>
        </div>
        <Progress :model-value="item.average_percent" class="h-2" />
      </div>
    </CardContent>
  </Card>
</template>
