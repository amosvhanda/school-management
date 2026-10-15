<script setup lang="ts">
import { computed, inject } from 'vue'
import { cn } from '@/lib/utils'
import { moduleHubKey } from '@/lib/module-hub'

const props = withDefaults(
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

const crumb = computed(() => props.eyebrow || `Dashboard / ${props.title}`)
</script>

<template>
  <div
    v-if="embedded"
    class="space-y-6"
  >
    <div
      v-if="$slots.actions"
      class="flex flex-wrap items-center justify-end gap-2 [&_button]:h-9 [&_button]:rounded-full [&_button]:px-4 [&_a]:h-9 [&_a]:rounded-full [&_a]:px-4"
    >
      <slot name="actions" />
    </div>
    <slot />
  </div>
  <div
    v-else
    :class="cn('mx-auto w-full space-y-6 pb-8', maxWidthClass[maxWidth])"
  >
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div class="min-w-0 space-y-1">
        <p
          class="text-xs text-muted-foreground"
        >
          <slot name="eyebrow">{{ crumb }}</slot>
        </p>
        <h1 class="font-heading text-2xl font-semibold tracking-tight text-foreground md:text-3xl">
          {{ title }}
        </h1>
        <p v-if="description" class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
          {{ description }}
        </p>
      </div>
      <div
        v-if="$slots.actions"
        class="flex flex-wrap items-center gap-2 [&_button]:h-9 [&_button]:rounded-full [&_button]:px-4 [&_a]:inline-flex [&_a]:h-9 [&_a]:items-center [&_a]:rounded-full [&_a]:px-4"
      >
        <slot name="actions" />
      </div>
    </header>

    <div class="space-y-6">
      <slot />
    </div>
  </div>
</template>
