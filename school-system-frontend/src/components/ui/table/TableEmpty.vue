<script setup lang="ts">
import type { Component } from 'vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import TableCell from './TableCell.vue'
import TableRow from './TableRow.vue'

withDefaults(
  defineProps<{
    colspan: number
    title?: string
    description?: string
    icon?: Component
  }>(),
  {
    title: 'No records found',
    description: 'Try adjusting search or filters, or create a new record.',
  },
)
</script>

<template>
  <TableRow class="hover:bg-transparent">
    <TableCell :colspan="colspan" class="h-44 p-0 text-center">
      <EmptyState
        variant="embedded"
        :title="title"
        :description="description"
        :icon="icon"
      />
    </TableCell>
  </TableRow>
</template>
