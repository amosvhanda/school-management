<script setup lang="ts">
import { computed, inject } from 'vue'
import { cn } from '@/lib/utils'
import { Separator } from '@/components/ui/separator'
import { moduleHubKey } from '@/lib/module-hub'

withDefaults(
  defineProps<{
    title: string
    description?: string
    maxWidth?: 'default' | 'wide' | 'full'
    eyebrow?: string
  }>(),
  { maxWidth: 'default' },
)

const hub = inject(moduleHubKey, null)
const embedded = computed(() => hub?.embedded === true)

const maxWidthClass = {
  default: 'max-w-[1400px]',
  wide: 'max-w-[1600px]',
  full: 'max-w-none',
} as const
</script>

<template>
  <div
    v-if="embedded"
    class="space-y-6"
  >
    <div
      v-if="$slots.actions"
      class="flex flex-wrap items-center justify-end gap-2"
    >
      <slot name="actions" />
    </div>
    <slot />
  </div>
  <div
    v-else
    :class="cn('mx-auto w-full space-y-8 pb-8', maxWidthClass[maxWidth])"
  >
    <header class="space-y-4">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0 space-y-1.5">
          <p
            v-if="eyebrow || $slots.eyebrow"
            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
          >
            <slot name="eyebrow">{{ eyebrow }}</slot>
          </p>
          <h1 class="font-heading text-2xl font-semibold tracking-tight text-foreground md:text-[1.75rem]">
            {{ title }}
          </h1>
          <p v-if="description" class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
            {{ description }}
          </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
          <slot name="actions" />
        </div>
      </div>
      <Separator class="opacity-60" />
    </header>

    <div class="space-y-6">
      <slot />
    </div>
  </div>
</template>
