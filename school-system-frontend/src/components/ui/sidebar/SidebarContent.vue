<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="sidebar-content"
    data-sidebar="content"
    :class="cn('no-scrollbar flex min-h-0 flex-1 flex-col gap-0 overflow-x-hidden overflow-y-auto', props.class)"
  >
    <slot />
  </div>
</template>
