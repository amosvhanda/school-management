<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'

withDefaults(
  defineProps<{
    title: string
    subtitle?: string
    /** Segments after Dashboard, e.g. ['People', 'Teachers'] */
    breadcrumbs?: string[]
    backTo: string | RouteLocationRaw
    backLabel?: string
    loading: boolean
    error: string | null
    initials?: string
    status?: string
  }>(),
  {
    backLabel: 'Back',
  },
)

defineEmits<{ retry: [] }>()

function statusBadgeVariant(status?: string): 'default' | 'secondary' | 'destructive' | 'outline' {
  const normalized = (status ?? '').toLowerCase()
  if (['active', 'current', 'approved', 'paid', 'returned'].includes(normalized)) return 'default'
  if (['pending', 'on_leave', 'partial', 'borrowed', 'draft'].includes(normalized)) return 'secondary'
  if (['inactive', 'suspended', 'terminated', 'overdue', 'rejected'].includes(normalized)) return 'destructive'
  return 'outline'
}
</script>

<template>
  <PageShell
    :title="title"
    :description="subtitle"
    :breadcrumbs="breadcrumbs"
    :back-to="backTo"
    :back-label="backLabel"
  >
    <template #actions>
      <slot name="actions" />
    </template>

    <PageLoader v-if="loading" :label="`Loading ${title}`" />
    <ErrorState v-else-if="error" :description="error" @retry="$emit('retry')" />

    <template v-else>
      <div
        v-if="initials || status"
        class="flex flex-wrap items-center gap-3"
      >
        <Avatar v-if="initials" class="size-14 shrink-0" size="lg">
          <AvatarFallback class="rounded-2xl bg-primary/10 text-base font-semibold text-primary">
            {{ initials }}
          </AvatarFallback>
        </Avatar>
        <Badge v-if="status" :variant="statusBadgeVariant(status)" class="capitalize">
          {{ status.replace(/_/g, ' ') }}
        </Badge>
      </div>

      <slot />
    </template>
  </PageShell>
</template>
