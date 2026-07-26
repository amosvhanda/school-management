<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
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
          <div class="relative space-y-3 text-white">
            <p class="text-xs font-semibold tracking-[0.18em] text-white/70 uppercase">
              {{ brandEyebrow ?? 'For Zimbabwe schools' }}
            </p>
            <h2 class="font-heading max-w-[14ch] text-3xl font-semibold leading-tight tracking-tight">
              {{ brandTitle ?? 'Run academics, fees, and families in one place' }}
            </h2>
          </div>
          <p class="relative max-w-sm text-sm leading-relaxed text-white/75">
            {{
              brandBody
                ?? 'Attendance, invoices, gradebook, and parent access — designed for day-to-day school operations.'
            }}
          </p>
        </aside>
      </CardContent>
    </Card>

    <slot name="below" />
  </div>
</template>
