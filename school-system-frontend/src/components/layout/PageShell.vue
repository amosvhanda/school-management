<script setup lang="ts">
import { cn } from '@/lib/utils'

withDefaults(
  defineProps<{
    title: string
    description?: string
    maxWidth?: 'default' | 'wide' | 'full'
  }>(),
  { maxWidth: 'default' },
)

const maxWidthClass = {
  default: 'max-w-[1400px]',
  wide: 'max-w-[1600px]',
  full: 'max-w-none',
} as const
</script>

<template>
  <div :class="cn('mx-auto w-full space-y-8 pb-8', maxWidthClass[maxWidth])">
    <header
      class="flex flex-col gap-4 border-b border-border/60 pb-6 sm:flex-row sm:items-end sm:justify-between"
    >
      <div class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight text-foreground">
          {{ title }}
        </h1>
        <p v-if="description" class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
          {{ description }}
        </p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </header>

    <div class="space-y-6">
      <slot />
    </div>
  </div>
</template>
