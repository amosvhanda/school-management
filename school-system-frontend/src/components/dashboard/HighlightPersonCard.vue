<script setup lang="ts">
import { computed } from 'vue'
import { Award, Star } from '@lucide/vue'
import { formatDate } from '@/lib/format'
import type { SchoolDashboardPerson } from '@/types/dashboard'

const props = defineProps<{
  tone: 'teacher' | 'student'
  badge: string
  person: SchoolDashboardPerson | null
  emptyTitle: string
  emptyDescription: string
}>()

const initials = computed(() => {
  const name = props.person?.name ?? ''
  return name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('') || '—'
})

const subtitle = computed(() => {
  if (!props.person) return ''
  return props.person.department || props.person.subject || props.person.class_name || props.person.email || ''
})

const stats = computed(() => {
  if (!props.person) return []
  if (props.tone === 'teacher') {
    return [
      { label: 'Results', value: props.person.result_count != null ? String(props.person.result_count) : '—' },
      { label: 'Marks', value: props.person.marks != null ? `${props.person.marks}` : '—' },
    ]
  }
  return [
    { label: 'Marks', value: props.person.marks != null ? String(props.person.marks) : '—' },
    { label: 'Results', value: props.person.result_count != null ? String(props.person.result_count) : '—' },
  ]
})
</script>

<template>
  <article
    class="relative overflow-hidden rounded-2xl p-5 text-white shadow-[var(--shadow-soft)]"
    :class="tone === 'teacher' ? 'bg-[var(--highlight-teacher)]' : 'bg-[var(--highlight-student)]'"
    :aria-label="person ? `${badge}: ${person.name}` : emptyTitle"
  >
    <div
      class="pointer-events-none absolute -right-8 -top-10 size-40 rounded-full bg-white/15"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -bottom-12 right-16 size-32 rounded-full bg-white/10"
      aria-hidden="true"
    />

    <p class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-medium">
      <Star class="size-3" aria-hidden="true" />
      {{ badge }}
    </p>

    <template v-if="person">
      <div class="relative mt-4 flex items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-lg font-semibold leading-tight">{{ person.name }}</h3>
          <p v-if="subtitle" class="mt-1 truncate text-sm text-white/80">{{ subtitle }}</p>
        </div>
        <div
          class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-lg font-semibold"
          aria-hidden="true"
        >
          {{ initials }}
        </div>
      </div>
      <p v-if="person.joined_on" class="relative mt-3 inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[11px]">
        <Award class="size-3" aria-hidden="true" />
        Joined {{ formatDate(person.joined_on) }}
      </p>
      <dl class="relative mt-5 grid grid-cols-2 gap-3 border-t border-white/20 pt-4 text-sm">
        <div v-for="stat in stats" :key="stat.label">
          <dt class="text-white/75">{{ stat.label }}</dt>
          <dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ stat.value }}</dd>
        </div>
      </dl>
    </template>

    <div v-else class="relative mt-6 space-y-1">
      <h3 class="text-base font-semibold">{{ emptyTitle }}</h3>
      <p class="text-sm text-white/80">{{ emptyDescription }}</p>
    </div>
  </article>
</template>
