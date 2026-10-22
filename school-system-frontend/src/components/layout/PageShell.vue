<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import { RouterLink } from 'vue-router'
import { computed, inject, useSlots } from 'vue'
import { ArrowLeft } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

defineOptions({ inheritAttrs: false })
import {
  CONTENT_MAX,
  PAGE_ACTIONS,
  PAGE_BREADCRUMB_CLASS,
  PAGE_STACK,
  PAGE_TITLE_CLASS,
  pageBreadcrumbs,
} from '@/lib/design-system'
import { moduleHubKey } from '@/lib/module-hub'

const props = withDefaults(
  defineProps<{
    title: string
    description?: string
    maxWidth?: 'default' | 'wide' | 'full'
    /** @deprecated Prefer `breadcrumbs` — slash-separated trail without title duplication in description */
    eyebrow?: string
    /** Breadcrumb segments after “Dashboard”, e.g. ['Finance', 'Invoices'] */
    breadcrumbs?: string[]
    backTo?: string | RouteLocationRaw
    backLabel?: string
    /** Extra classes on the standalone page root (e.g. print overrides). */
    rootClass?: string
  }>(),
  { maxWidth: 'wide', backLabel: 'Back' },
)

const slots = useSlots()
const hub = inject(moduleHubKey, null)
const embedded = computed(() => hub?.embedded === true)

const maxWidthClass = CONTENT_MAX

const crumbParts = computed(() => {
  if (props.breadcrumbs?.length) {
    return pageBreadcrumbs(...props.breadcrumbs)
  }
  if (props.eyebrow?.trim()) {
    return props.eyebrow.split('/').map((part) => part.trim()).filter(Boolean)
  }
  return pageBreadcrumbs(props.title)
})
</script>

<template>
  <div
    v-if="embedded"
    class="space-y-6"
  >
    <div
      v-if="$slots.actions"
      :class="cn(PAGE_ACTIONS, 'justify-end')"
    >
      <slot name="actions" />
    </div>
    <slot />
  </div>
  <div
    v-else
    v-bind="$attrs"
    :class="cn('mx-auto w-full pb-8', PAGE_STACK, maxWidthClass[maxWidth], rootClass)"
  >
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 space-y-2.5">
        <slot name="leading" />
        <Button
          v-if="backTo && !$slots.leading"
          variant="ghost"
          size="sm"
          class="-ml-2 h-8 px-2 text-muted-foreground print:hidden"
          as-child
        >
          <RouterLink :to="backTo">
            <ArrowLeft class="mr-1 size-4" aria-hidden="true" />
            {{ backLabel }}
          </RouterLink>
        </Button>
        <nav
          v-if="!slots.eyebrow"
          :class="cn(PAGE_BREADCRUMB_CLASS, 'print:hidden')"
          aria-label="Breadcrumb"
        >
          <template v-for="(part, index) in crumbParts" :key="`${part}-${index}`">
            <span
              :class="index === crumbParts.length - 1 ? 'font-medium text-foreground/80' : ''"
            >{{ part }}</span>
            <span
              v-if="index < crumbParts.length - 1"
              class="text-border select-none"
              aria-hidden="true"
            >/</span>
          </template>
        </nav>
        <div v-else class="text-sm text-muted-foreground">
          <slot name="eyebrow" />
        </div>
        <h1 :class="PAGE_TITLE_CLASS">
          {{ title }}
        </h1>
        <p v-if="description" class="max-w-2xl text-base leading-relaxed text-muted-foreground">
          {{ description }}
        </p>
      </div>
      <div
        v-if="$slots.actions"
        :class="cn(PAGE_ACTIONS, 'sm:pt-1')"
      >
        <slot name="actions" />
      </div>
    </header>

    <div :class="PAGE_STACK">
      <slot />
    </div>
  </div>
</template>
