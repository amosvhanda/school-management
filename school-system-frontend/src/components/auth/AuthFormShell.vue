<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { brandName, brandPanel } from '@/lib/brand'
import { cn } from '@/lib/utils'

defineProps<{
  class?: HTMLAttributes['class']
  brandEyebrow?: string
  brandTitle?: string
  brandBody?: string
}>()
</script>

<template>
  <div :class="cn('flex w-full max-w-sm flex-col gap-5 md:max-w-4xl', $props.class)">
    <slot name="above" />

    <Card class="overflow-hidden p-0">
      <CardContent class="grid p-0 md:grid-cols-2">
        <div class="space-y-6 bg-card p-6 md:p-8">
          <slot />
        </div>

        <aside
          class="auth-brand-panel relative hidden overflow-hidden md:flex md:flex-col md:justify-between md:p-8"
          aria-hidden="true"
        >
          <div
            class="pointer-events-none absolute inset-0 opacity-30"
            style="
              background-image:
                linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px);
              background-size: 28px 28px;
            "
          />
          <div class="relative space-y-4 text-white">
            <p class="text-xs font-semibold tracking-[0.18em] text-white/80 uppercase">
              {{ brandEyebrow ?? brandPanel.eyebrow }}
            </p>
            <p class="font-heading text-4xl font-semibold leading-none tracking-tight">
              {{ brandName }}
            </p>
            <h2 class="max-w-[18ch] text-lg font-medium leading-snug text-white/90">
              {{ brandTitle ?? brandPanel.title }}
            </h2>
          </div>
          <p class="relative max-w-sm text-sm leading-relaxed text-white/80">
            {{ brandBody ?? brandPanel.body }}
          </p>
        </aside>
      </CardContent>
    </Card>

    <slot name="below" />
  </div>
</template>
