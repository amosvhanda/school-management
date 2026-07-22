<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/lib/utils'

withDefaults(
  defineProps<{
    title: string
    description?: string
    /** Optional icon shown in the meta panel. */
    icon?: Component
    /** Label above the meta value (e.g. Records). */
    metaLabel?: string
    /** Large meta value (e.g. record count). */
    metaValue?: string | number
    /** Remove content padding (e.g. full-bleed tables). */
    flush?: boolean
    class?: string
  }>(),
  {
    flush: false,
  },
)
</script>

<template>
  <Card :class="cn('overflow-hidden border-border/70 shadow-sm', $props.class)">
    <CardHeader class="border-b border-border/60 bg-gradient-to-r from-background via-background to-muted/40 px-6 py-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="min-w-0 space-y-2">
          <CardTitle class="text-xl">{{ title }}</CardTitle>
          <CardDescription v-if="description || $slots.description" class="max-w-2xl text-sm leading-relaxed">
            <slot name="description">{{ description }}</slot>
          </CardDescription>
        </div>

        <div
          v-if="$slots.meta || metaLabel != null || metaValue != null"
          class="flex items-center gap-4 rounded-2xl border border-border/60 bg-background px-5 py-4"
        >
          <slot name="meta">
            <div
              v-if="icon"
              class="inline-flex size-16 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
            >
              <component :is="icon" class="size-7" aria-hidden="true" />
            </div>
            <div class="space-y-1.5">
              <p v-if="metaLabel" class="text-sm font-medium text-foreground">{{ metaLabel }}</p>
              <p
                v-if="metaValue != null"
                class="text-2xl font-semibold tracking-tight text-foreground"
              >
                {{ metaValue }}
              </p>
            </div>
          </slot>
        </div>
      </div>
    </CardHeader>

    <CardContent :class="flush ? 'p-0' : 'px-6 py-6'">
      <slot />
    </CardContent>
  </Card>
</template>
