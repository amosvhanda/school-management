<script setup lang="ts">
import PageShell from '@/components/layout/PageShell.vue'
import { Button } from '@/components/ui/button'
import { formatTime } from '@/lib/format'

defineProps<{
  title: string
  description: string
  breadcrumbs?: string[]
  loading?: boolean
  lastUpdated?: Date | null
}>()

defineEmits<{ refresh: [] }>()
</script>

<template>
  <PageShell
    :title="title"
    :description="description"
    :breadcrumbs="breadcrumbs ?? [title]"
  >
    <template #actions>
      <slot name="actions" />
      <span
        v-if="lastUpdated"
        class="hidden text-xs text-muted-foreground sm:inline"
      >
        Updated {{ formatTime(lastUpdated) }}
      </span>
      <Button
        variant="outline"
        size="sm"
        :disabled="loading"
        :aria-busy="loading"
        @click="$emit('refresh')"
      >
        Refresh
      </Button>
    </template>
    <slot />
  </PageShell>
</template>
