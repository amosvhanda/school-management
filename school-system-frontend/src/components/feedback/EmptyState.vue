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
      'flex flex-col items-center justify-center gap-3 text-center',
      variant === 'default' && 'rounded-2xl border border-dashed border-border/70 bg-card/80 p-10 shadow-[var(--shadow-soft)] sm:p-12',
      variant === 'embedded' && 'px-6 py-12',
      $props.class,
    )"
    role="status"
  >
    <div
      class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/15"
      aria-hidden="true"
    >
      <component :is="icon ?? Inbox" class="size-5" />
    </div>
    <div class="space-y-1.5">
      <h3 class="text-base font-semibold tracking-tight text-foreground">
        {{ title ?? 'No records found' }}
      </h3>
      <p class="max-w-md text-sm leading-relaxed text-muted-foreground">
        {{ description ?? 'There is nothing to show here yet.' }}
      </p>
    </div>
    <div v-if="$slots.default" class="mt-1">
      <slot />
    </div>
  </div>
</template>
