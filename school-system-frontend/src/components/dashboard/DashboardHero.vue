<script setup lang="ts">
import { computed } from 'vue'
import { GraduationCap, RefreshCw } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { formatDate, formatTime } from '@/lib/format'
import { PAGE_BREADCRUMB_CLASS, PAGE_TITLE_CLASS } from '@/lib/page-meta'

const props = defineProps<{
  name?: string
  role?: string
  subtitle?: string
  loading?: boolean
  lastUpdated?: Date | null
  pageTitle?: string
  /** When true, page chrome is provided by PageShell (greeting strip only). */
  hidePageHeader?: boolean
  /** Hide refresh / updated row (e.g. when PageShell actions slot owns it). */
  hideToolbar?: boolean
}>()

defineEmits<{ refresh: [] }>()

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good Morning'
  if (hour < 17) return 'Good Afternoon'
  return 'Good Evening'
})

const todayLabel = computed(() => {
  const now = new Date()
  const weekday = now.toLocaleDateString('en-GB', {
    weekday: 'short',
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
  <div :class="hidePageHeader ? '' : 'space-y-4'">
    <header v-if="pageTitle && !hidePageHeader" class="flex flex-col gap-2.5">
      <nav :class="PAGE_BREADCRUMB_CLASS" aria-label="Breadcrumb">
        <span>Dashboard</span>
        <span class="text-border select-none" aria-hidden="true">/</span>
        <span class="font-medium text-foreground/80">{{ pageTitle }}</span>
      </nav>
      <h1 :class="PAGE_TITLE_CLASS">
        {{ pageTitle }}
      </h1>
    </header>

    <section
      class="relative flex flex-col gap-4 overflow-hidden rounded-2xl bg-card p-4 shadow-[var(--shadow-soft)] ring-1 ring-border/80 sm:flex-row sm:items-center sm:justify-between sm:p-5 dark:ring-border/50"
      :aria-labelledby="pageTitle ? undefined : 'dashboard-greeting'"
    >
      <div
        class="pointer-events-none absolute inset-y-0 left-0 w-1 bg-primary"
        aria-hidden="true"
      />
      <div class="flex min-w-0 items-start gap-4 sm:items-center">
        <div
          class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-sm"
          aria-hidden="true"
        >
          <GraduationCap class="size-7" />
        </div>
        <div class="min-w-0 space-y-1">
          <p class="text-xs font-medium capitalize text-muted-foreground">{{ roleLabel }} · {{ todayLabel }}</p>
          <h2
            :id="pageTitle ? undefined : 'dashboard-greeting'"
            class="text-xl font-semibold tracking-tight sm:text-2xl"
          >
            {{ greeting }}, {{ name?.split(' ')[0] ?? 'there' }}
          </h2>
          <p class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
            {{ heroSubtitle }}
          </p>
        </div>
      </div>

      <div v-if="!hideToolbar" class="flex shrink-0 flex-wrap items-center gap-2">
        <p v-if="lastUpdated" class="hidden text-xs text-muted-foreground lg:block">
          Updated {{ updatedLabel }}
        </p>
        <slot name="actions" />
        <Button
          variant="outline"
          size="sm"
          class="h-9 gap-2 rounded-xl px-4 text-xs"
          :disabled="loading"
          :aria-busy="loading"
          @click="$emit('refresh')"
        >
          <RefreshCw class="size-3.5" :class="loading && 'animate-spin'" aria-hidden="true" />
          Refresh
        </Button>
      </div>
    </section>
  </div>
</template>
