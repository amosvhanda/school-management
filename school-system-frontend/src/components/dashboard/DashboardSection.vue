<script setup lang="ts">
defineProps<{
  title: string
  description?: string
  /** Optional heading level for a11y hierarchy */
  heading?: 'h2' | 'h3'
  titleId?: string
}>()
</script>

<template>
  <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0 space-y-1">
      <component
        :is="heading ?? 'h2'"
        :id="titleId"
        class="text-base font-semibold tracking-tight text-foreground md:text-lg"
      >
        {{ title }}
      </component>
      <p v-if="description" class="text-sm text-muted-foreground">
        {{ description }}
      </p>
    </div>
    <div v-if="$slots.actions" class="mt-2 flex shrink-0 items-center gap-2 sm:mt-0">
      <slot name="actions" />
    </div>
  </div>
</template>
