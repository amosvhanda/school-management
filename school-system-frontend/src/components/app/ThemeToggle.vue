<script setup lang="ts">
// Corrected Lucide import path coordinates
import { Moon, Sun } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { useUiStore } from '@/stores/ui.store'

import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    variant?: 'ghost' | 'outline'
    size?: 'default' | 'sm' | 'icon'
    class?: HTMLAttributes['class']
  }>(),
  {
    variant: 'ghost',
    size: 'icon',
  },
)

const uiStore = useUiStore()
</script>

<template>
  <Button
    :variant="variant"
    :size="size"
    :class="cn(size === 'icon' ? 'size-9' : undefined, props.class)"
    :aria-label="uiStore.theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
    @click="uiStore.toggleTheme()"
  >
    <Sun v-if="uiStore.theme === 'dark'" class="size-4" aria-hidden="true" />
    <Moon v-else class="size-4" aria-hidden="true" />
  </Button>
</template>
