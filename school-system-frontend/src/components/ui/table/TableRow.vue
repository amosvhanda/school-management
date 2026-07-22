<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { tableRowClass } from './table-chrome'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <tr
    data-slot="table-row"
    :class="cn(tableRowClass, props.class)"
  >
    <slot />
  </tr>
</template>
