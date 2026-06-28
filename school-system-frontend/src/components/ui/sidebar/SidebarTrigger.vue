<script setup lang="ts">
import { PanelLeftIcon } from '@lucide/vue';

import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'
import { useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { toggleSidebar, open, openMobile, isMobile, state } = useSidebar()
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn('size-9 shrink-0', props.class)"
    :aria-expanded="isMobile ? openMobile : open"
    :aria-controls="isMobile ? 'mobile-sidebar' : 'desktop-sidebar'"
    :aria-label="state === 'collapsed' ? 'Expand sidebar' : 'Collapse sidebar'"
    @click="toggleSidebar"
  >
    <PanelLeftIcon class="cn-rtl-flip" />
    <span class="sr-only">Toggle Sidebar</span>
  </Button>
</template>
