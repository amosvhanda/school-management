<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/lib/utils'

withDefaults(
  defineProps<{
    title: string
    description?: string
    icon?: Component
    metaLabel?: string
    metaValue?: string | number
    flush?: boolean
    class?: string
  }>(),
  {
    flush: false,
  },
)
</script>

<template>
  <Card :class="cn('overflow-hidden', $props.class)">
    <CardHeader class="border-b border-border/60 px-5 py-5 sm:px-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="min-w-0 space-y-1">
          <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Records</p>
          <CardTitle class="text-lg">{{ title }}</CardTitle>
          <CardDescription v-if="description || $slots.description" class="max-w-2xl text-sm leading-relaxed">
            <slot name="description">{{ description }}</slot>
          </CardDescription>
        </div>

        <div
          v-if="$slots.meta || metaLabel != null || metaValue != null"
          class="flex items-center gap-3 rounded-2xl bg-muted/50 px-4 py-3 ring-1 ring-border/60"
        >
          <slot name="meta">
            <div
              v-if="icon"
              class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
            >
              <component :is="icon" class="size-5" aria-hidden="true" />
            </div>
            <div class="space-y-0.5">
              <p v-if="metaLabel" class="text-xs text-muted-foreground">{{ metaLabel }}</p>
              <p
                v-if="metaValue != null"
                class="text-2xl font-semibold tracking-tight tabular-nums text-foreground"
              >
                {{ metaValue }}
              </p>
            </div>
          </slot>
        </div>
      </div>
    </CardHeader>

    <CardContent :class="flush ? 'p-0' : 'px-5 py-5 sm:px-6'">
      <slot />
    </CardContent>
  </Card>
</template>
