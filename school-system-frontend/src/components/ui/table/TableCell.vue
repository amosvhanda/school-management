<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { tableCellClass } from './table-chrome'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <td
    data-slot="table-cell"
    :class="cn(tableCellClass, 'align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0', props.class)"
  >
    <slot />
  </td>
</template>
