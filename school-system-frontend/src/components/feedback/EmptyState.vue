<script setup lang="ts">
import type { Component } from 'vue'
import { Inbox } from '@lucide/vue'
import { cn } from '@/lib/utils'

withDefaults(
  defineProps<{
    title?: string
    description?: string
    /** embedded = no dashed border (for use inside tables/cards) */
    variant?: 'default' | 'embedded'
    icon?: Component
    class?: string
  }>(),
  {
    variant: 'default',
  },
)
</script>

<template>
  <div
    :class="cn(
      'flex flex-col items-center justify-center gap-2 text-center',
      variant === 'default' && 'rounded-xl border border-dashed border-border/80 bg-muted/10 p-12',
      variant === 'embedded' && 'px-6 py-12',
      $props.class,
    )"
    role="status"
  >
    <div
      class="mb-1 flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground"
      aria-hidden="true"
    >
      <component :is="icon ?? Inbox" class="size-5" />
    </div>
    <h3 class="text-base font-semibold tracking-tight text-foreground">
      {{ title ?? 'No records found' }}
    </h3>
    <p class="max-w-md text-sm text-muted-foreground">
      {{ description ?? 'There is nothing to show here yet.' }}
    </p>
    <div v-if="$slots.default" class="mt-2">
      <slot />
    </div>
  </div>
</template>
