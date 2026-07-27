<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import { RouterLink } from 'vue-router'
import { ArrowLeft } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

withDefaults(
  defineProps<{
    title: string
    subtitle?: string
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
  <div class="space-y-6">
    <Button variant="ghost" size="sm" as-child class="-ml-2">
      <RouterLink :to="backTo">
        <ArrowLeft class="mr-1 size-4" aria-hidden="true" />
        {{ backLabel }}
      </RouterLink>
    </Button>

    <PageLoader v-if="loading" :label="`Loading ${title}`" />
    <ErrorState v-else-if="error" :description="error" @retry="$emit('retry')" />

    <template v-else>
      <Card>
        <CardContent class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex min-w-0 items-center gap-4">
            <Avatar v-if="initials" class="size-14 shrink-0" size="lg">
              <AvatarFallback class="text-base font-semibold">{{ initials }}</AvatarFallback>
            </Avatar>
            <div class="min-w-0 space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="truncate text-2xl font-semibold tracking-tight">{{ title }}</h1>
                <Badge v-if="status" :variant="statusBadgeVariant(status)" class="capitalize">
                  {{ status.replace(/_/g, ' ') }}
                </Badge>
              </div>
              <p v-if="subtitle" class="truncate text-sm text-muted-foreground">{{ subtitle }}</p>
            </div>
          </div>
          <div class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
          </div>
        </CardContent>
      </Card>

      <slot />
    </template>
  </div>
</template>
