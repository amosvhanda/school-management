<script setup lang="ts">
import { computed } from 'vue'
import { RefreshCw } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

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

const todayLabel = computed(() =>
  new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' }),
)

const updatedLabel = computed(() => {
  if (!props.lastUpdated) return ''
  return props.lastUpdated.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
})

const roleLabel = computed(() =>
  (props.role ?? 'staff').replace(/_/g, ' '),
)

const heroSubtitle = computed(() =>
  props.subtitle ?? 'Executive overview of enrolment, finance, attendance, and school operations.',
)
</script>

<template>
  <section
    class="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-card via-card to-primary/[0.03] p-6 shadow-sm md:p-8"
    aria-labelledby="dashboard-hero-title"
  >
    <div class="absolute -top-24 -right-24 size-64 rounded-full bg-primary/[0.05] blur-3xl" aria-hidden="true" />
    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
      <div class="space-y-3">
        <div class="flex flex-wrap items-center gap-2">
          <Badge variant="secondary" class="font-normal text-xs capitalize px-2.5 py-0.5">{{ roleLabel }}</Badge>
          <span class="text-xs text-muted-foreground">{{ todayLabel }}</span>
        </div>
        <div>
          <h1 id="dashboard-hero-title" class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">
            {{ greeting }}, {{ name?.split(' ')[0] ?? 'there' }}
          </h1>
          <p class="mt-1 max-w-xl text-sm text-muted-foreground md:text-base leading-relaxed">
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
          class="gap-2 bg-background/80 backdrop-blur-sm h-9 px-3 text-xs"
          :disabled="loading"
          @click="$emit('refresh')"
        >
          <RefreshCw class="size-3.5" :class="loading && 'animate-spin'" />
          Refresh
        </Button>
      </div>
    </div>
  </section>
</template>
