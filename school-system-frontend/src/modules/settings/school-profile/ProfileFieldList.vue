<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'

export type ProfileField = {
  label: string
  value: string
  badge?: boolean
  muted?: boolean
  set?: boolean
}

defineProps<{
  fields: ProfileField[]
  title?: string
  description?: string
  statusLabel?: string
  statusTone?: 'success' | 'warning' | 'muted'
}>()
</script>

<template>
  <section class="overflow-hidden rounded-2xl border border-border/70 bg-card shadow-[var(--shadow-soft)]">
    <header
      v-if="title || description || statusLabel"
      class="flex flex-wrap items-start justify-between gap-3 border-b border-border/60 px-4 py-4 sm:px-5"
    >
      <div class="min-w-0 space-y-1">
        <h3 v-if="title" class="text-base font-semibold text-foreground">{{ title }}</h3>
        <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
      </div>
      <Badge
        v-if="statusLabel"
        :variant="statusTone === 'warning' ? 'warning' : statusTone === 'muted' ? 'secondary' : 'success'"
        class="shrink-0"
      >
        {{ statusLabel }}
      </Badge>
    </header>

    <ul class="divide-y divide-border/60" role="list">
      <li
        v-for="field in fields"
        :key="field.label"
        class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-5"
      >
        <div class="flex min-w-0 items-start gap-2.5">
          <span
            :class="cn(
              'mt-1.5 size-1.5 shrink-0 rounded-full',
              field.set === false || field.muted ? 'bg-muted-foreground/40' : 'bg-chart-2',
            )"
            aria-hidden="true"
          />
          <span class="text-sm text-muted-foreground">{{ field.label }}</span>
        </div>
        <div class="min-w-0 pl-4 sm:pl-0 sm:text-right">
          <Badge v-if="field.badge && field.set !== false" variant="secondary" class="font-mono text-xs">
            {{ field.value }}
          </Badge>
          <span
            v-else
            :class="cn(
              'block break-words text-sm font-medium',
              field.muted || field.set === false ? 'italic text-muted-foreground' : 'text-foreground',
            )"
          >
            {{ field.value }}
          </span>
        </div>
      </li>
    </ul>

    <footer v-if="$slots.footer" class="border-t border-border/60 px-4 py-3 sm:px-5">
      <slot name="footer" />
    </footer>
  </section>
</template>
