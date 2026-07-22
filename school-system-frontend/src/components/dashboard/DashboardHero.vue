<script setup lang="ts">
import { computed } from 'vue'
import { RefreshCw } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { formatDate, formatTime } from '@/lib/format'

const props = defineProps<{
  name?: string
  role?: string
  subtitle?: string
  loading?: boolean
  lastUpdated?: Date | null
}>()

defineEmits<{ refresh: [] }>()

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 17) return 'Good afternoon'
  return 'Good evening'
})

const todayLabel = computed(() => {
  const now = new Date()
  const weekday = now.toLocaleDateString('en-GB', {
    weekday: 'long',
    timeZone: 'Africa/Harare',
  })
  return `${weekday}, ${formatDate(now)}`
})

const updatedLabel = computed(() => {
  if (!props.lastUpdated) return ''
  return formatTime(props.lastUpdated)
})

const roleLabel = computed(() =>
  (props.role ?? 'staff').replace(/_/g, ' '),
)

const heroSubtitle = computed(() =>
  props.subtitle ?? 'Overview of enrolment, finance, attendance, and school operations.',
)
</script>

<template>
  <header class="flex flex-col gap-4 border-b border-border/60 pb-6 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0 space-y-2">
      <div class="flex flex-wrap items-center gap-2">
        <Badge variant="secondary" class="px-2.5 py-0.5 text-xs font-normal capitalize">
          {{ roleLabel }}
        </Badge>
        <span class="text-xs text-muted-foreground">{{ todayLabel }}</span>
      </div>
      <div class="space-y-1">
        <h1 class="font-heading text-2xl font-semibold tracking-tight text-foreground md:text-[1.75rem]">
          {{ greeting }}, {{ name?.split(' ')[0] ?? 'there' }}
        </h1>
        <p class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
          {{ heroSubtitle }}
        </p>
      </div>
    </div>

    <div class="flex shrink-0 flex-wrap items-center gap-3">
      <p v-if="lastUpdated" class="hidden text-xs text-muted-foreground sm:block">
        Updated {{ updatedLabel }}
      </p>
      <slot name="actions" />
      <Button
        variant="outline"
        size="sm"
        class="h-9 gap-2 px-3 text-xs"
        :disabled="loading"
        :aria-busy="loading"
        @click="$emit('refresh')"
      >
        <RefreshCw class="size-3.5" :class="loading && 'animate-spin'" aria-hidden="true" />
        Refresh
      </Button>
    </div>
  </header>
</template>
