<script lang="ts" setup>
import {
  CircleCheckIcon,
  InfoIcon,
  TriangleAlertIcon,
  OctagonXIcon,
  Loader2Icon,
  XIcon,
} from '@lucide/vue'
import type { ToasterProps } from 'vue-sonner'
import { Toaster as Sonner } from 'vue-sonner'
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<ToasterProps>()

const sonnerProps = computed(() => {
  const { toastOptions, class: className, ...rest } = props
  return {
    ...rest,
    class: cn('toaster group', className),
    toastOptions: {
      ...(toastOptions ?? {}),
      classes: {
        ...(toastOptions?.classes ?? {}),
        toast: cn('rounded-2xl', toastOptions?.classes?.toast),
      },
    },
  }
})
</script>

<template>
  <Sonner
    v-bind="sonnerProps"
    :style="{
      '--normal-bg': 'var(--popover)',
      '--normal-text': 'var(--popover-foreground)',
      '--normal-border': 'var(--border)',
      '--border-radius': 'var(--radius)',
      '--gray2': 'hsl(var(--popover) / 0.9)',
      '--gray3': 'var(--border)',
      '--gray4': 'var(--border)',
      '--gray5': 'var(--border)',
      '--gray12': 'var(--popover-foreground)',
    }"
  >
    <template #success-icon>
      <CircleCheckIcon class="size-4" />
    </template>
    <template #info-icon>
      <InfoIcon class="size-4" />
    </template>
    <template #warning-icon>
      <TriangleAlertIcon class="size-4" />
    </template>
    <template #error-icon>
      <OctagonXIcon class="size-4" />
    </template>
    <template #loading-icon>
      <div>
        <Loader2Icon class="size-4 animate-spin" />
      </div>
    </template>
    <template #close-icon>
      <XIcon class="size-4" />
    </template>
  </Sonner>
</template>
