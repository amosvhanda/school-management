<script setup lang="ts">
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'

withDefaults(
  defineProps<{
    title: string
    description?: string
    loading?: boolean
    loadingLabel?: string
    error?: string | null
    empty?: boolean
    emptyTitle?: string
    emptyDescription?: string
  }>(),
  {
    loading: false,
    loadingLabel: 'Loading…',
    error: null,
    empty: false,
    emptyTitle: 'Nothing here yet',
    emptyDescription: 'When there is data, it will show up here.',
  },
)

defineEmits<{
  retry: []
}>()
</script>

<template>
  <section class="space-y-5" :aria-labelledby="undefined">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div class="min-w-0 space-y-1">
        <h2 class="font-heading text-lg font-semibold tracking-tight text-foreground">
          {{ title }}
        </h2>
        <p v-if="description" class="max-w-xl text-sm text-muted-foreground">
          {{ description }}
        </p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </header>

    <PageLoader v-if="loading" :label="loadingLabel" />
    <ErrorState
      v-else-if="error"
      :description="error"
      @retry="$emit('retry')"
    />
    <div
      v-else-if="empty"
      class="rounded-2xl border border-dashed border-border/70 px-6 py-12 text-center"
      role="status"
    >
      <p class="text-sm font-medium text-foreground">{{ emptyTitle }}</p>
      <p class="mt-1 text-sm text-muted-foreground">{{ emptyDescription }}</p>
      <div v-if="$slots.emptyAction" class="mt-4 flex justify-center">
        <slot name="emptyAction" />
      </div>
    </div>
    <div v-else class="space-y-5">
      <slot />
    </div>
  </section>
</template>
