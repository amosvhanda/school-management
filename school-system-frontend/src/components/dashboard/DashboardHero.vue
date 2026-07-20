<script setup lang="ts">
import { computed } from 'vue'
import { RefreshCw } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
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
  props.subtitle ?? 'Executive overview of enrolment, finance, attendance, and school operations.',
)
</script>

<template>
  <Card
    class="relative overflow-hidden border-0 bg-gradient-to-br from-card via-card to-primary/[0.04] shadow-sm ring-1 ring-foreground/10"
    aria-labelledby="dashboard-hero-title"
  >
    <div
      class="pointer-events-none absolute -top-24 -right-24 size-64 rounded-full bg-primary/[0.06] blur-3xl"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -bottom-20 -left-16 size-48 rounded-full bg-chart-2/10 blur-3xl"
      aria-hidden="true"
    />
    <CardContent class="relative px-5 py-6 md:px-8 md:py-8">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-3">
          <div class="flex flex-wrap items-center gap-2">
            <Badge variant="secondary" class="px-2.5 py-0.5 text-xs font-normal capitalize">
              {{ roleLabel }}
            </Badge>
            <span class="text-xs text-muted-foreground">{{ todayLabel }}</span>
          </div>
          <div>
            <h1
              id="dashboard-hero-title"
              class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl"
            >
              {{ greeting }}, {{ name?.split(' ')[0] ?? 'there' }}
            </h1>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-muted-foreground md:text-base">
              {{ heroSubtitle }}
            </p>
          </div>
        </div>
        <div class="flex shrink-0 items-center gap-3">
          <p v-if="lastUpdated" class="hidden text-xs text-muted-foreground sm:block">
            Updated {{ updatedLabel }}
          </p>
          <Button
            variant="outline"
            size="sm"
            class="h-9 gap-2 bg-background/80 px-3 text-xs backdrop-blur-sm"
            :disabled="loading"
            @click="$emit('refresh')"
          >
            <RefreshCw class="size-3.5" :class="loading && 'animate-spin'" aria-hidden="true" />
            Refresh
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
