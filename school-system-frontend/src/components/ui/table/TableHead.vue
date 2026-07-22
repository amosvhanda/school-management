<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { tableHeadClass } from './table-chrome'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <th
    data-slot="table-head"
    :class="cn(tableHeadClass, 'text-left align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0', props.class)"
  >
    <slot />
  </th>
</template>
