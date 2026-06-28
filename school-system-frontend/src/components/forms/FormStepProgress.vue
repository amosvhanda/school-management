<script setup lang="ts">
import { computed } from 'vue'
import type { FormStep } from '@/lib/form-steps'
import { cn } from '@/lib/utils'

const props = defineProps<{
  steps: FormStep[]
  currentStep: number
}>()

const current = computed(() => props.steps[props.currentStep])
const progress = computed(() => ((props.currentStep + 1) / props.steps.length) * 100)
</script>

<template>
  <div class="space-y-3" aria-live="polite">
    <div class="flex items-center justify-between gap-2 text-sm">
      <p class="font-medium text-foreground">
        {{ current?.title }}
      </p>
      <p class="shrink-0 text-xs text-muted-foreground">
        Step {{ currentStep + 1 }} of {{ steps.length }}
      </p>
    </div>

    <div
      class="h-1 w-full overflow-hidden rounded-full bg-muted"
      role="progressbar"
      :aria-valuenow="currentStep + 1"
      :aria-valuemin="1"
      :aria-valuemax="steps.length"
      :aria-label="`Step ${currentStep + 1} of ${steps.length}: ${current?.title}`"
    >
      <div
        class="h-full rounded-full bg-primary transition-[width] duration-300 ease-out"
        :style="{ width: `${progress}%` }"
      />
    </div>

    <ol class="hidden gap-1 sm:flex" aria-label="Form steps">
      <li
        v-for="(step, index) in steps"
        :key="step.id"
        class="flex min-w-0 flex-1 items-center"
      >
        <span
          :class="cn(
            'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-medium transition-colors',
            index < currentStep && 'bg-primary/15 text-primary',
            index === currentStep && 'bg-primary text-primary-foreground',
            index > currentStep && 'bg-muted text-muted-foreground',
          )"
          :aria-current="index === currentStep ? 'step' : undefined"
        >
          {{ index + 1 }}
        </span>
        <span
          :class="cn(
            'ml-2 truncate text-xs',
            index === currentStep ? 'font-medium text-foreground' : 'text-muted-foreground',
          )"
        >
          {{ step.title }}
        </span>
        <span
          v-if="index < steps.length - 1"
          class="mx-2 h-px min-w-3 flex-1 bg-border"
          aria-hidden="true"
        />
      </li>
    </ol>
  </div>
</template>
