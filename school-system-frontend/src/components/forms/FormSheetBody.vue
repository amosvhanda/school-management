<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import type { z } from 'zod'
import FormBuilder from '@/components/forms/FormBuilder.vue'
import FormStepProgress from '@/components/forms/FormStepProgress.vue'
import { useFormBuilder } from '@/components/forms/useFormBuilder'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { ChevronLeft, Loader2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { SheetFooter } from '@/components/ui/sheet'
import { Skeleton } from '@/components/ui/skeleton'
import { formButtonClass, type FormSheetColumns, type FormSheetSize } from '@/lib/form-standards'
import { buildFormSteps, shouldUseFormSteps } from '@/lib/form-steps'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    fields: FormFieldSchema[]
    schema: z.ZodTypeAny
    formKey?: string
    resetValues?: Record<string, unknown>
    saving?: boolean
    formLoading?: boolean
    saveLabel?: string
    savingLabel?: string
    cancelLabel?: string
    columns?: FormSheetColumns
    /** Dialog size — drives body min-height so short forms are not padded tall. */
    size?: FormSheetSize
    /** Enable multi-step wizard when the form has multiple sections. */
    staged?: boolean
  }>(),
  {
    saveLabel: 'Save',
    savingLabel: 'Saving…',
    cancelLabel: 'Cancel',
    columns: 2,
    size: 'lg',
    formLoading: false,
  },
)

const emit = defineEmits<{
  submit: [values: Record<string, unknown>]
  cancel: []
}>()

const {
  handleSubmit,
  resetForm,
  applyServerErrors,
  validateField,
  isSubmitting,
} = useFormBuilder(props.schema)

defineExpose({ applyServerErrors })

const currentStep = ref(0)
const steps = computed(() => buildFormSteps(props.fields))
const isStaged = computed(() => shouldUseFormSteps(props.fields, props.staged))
const isLastStep = computed(() => currentStep.value >= steps.value.length - 1)

function applyResetValues(values?: Record<string, unknown>) {
  resetForm({ values: (values ?? {}) as never })
  currentStep.value = 0
}

watch(
  () => [props.formKey, props.resetValues] as const,
  async ([, values]) => {
    await nextTick()
    applyResetValues(values)
  },
  { deep: true, immediate: true },
)

const onSubmit = handleSubmit((values) => {
  emit('submit', values as Record<string, unknown>)
})

async function validateCurrentStep(): Promise<boolean> {
  if (!isStaged.value) return true

  const step = steps.value[currentStep.value]
  if (!step) return true

  let valid = true
  for (const name of step.fieldNames) {
    const result = await validateField(name as never)
    if (!result.valid) valid = false
  }
  return valid
}

async function goNext() {
  if (await validateCurrentStep()) {
    currentStep.value += 1
  }
}

function goBack() {
  if (currentStep.value > 0) {
    currentStep.value -= 1
  }
}

async function commitFocusedField() {
  const active = document.activeElement
  if (active instanceof HTMLElement) {
    active.blur()
    await nextTick()
  }
}

async function onPrimaryAction() {
  await commitFocusedField()

  if (isStaged.value && !isLastStep.value) {
    await goNext()
    return
  }
  await onSubmit()
}

const busy = () => props.saving || props.formLoading || isSubmitting.value
const primaryLabel = computed(() => {
  if (busy()) return props.savingLabel
  if (isStaged.value && !isLastStep.value) return 'Continue'
  return props.saveLabel
})

/** Short forms hug content; larger / loading forms keep a stable scroll area. */
const bodyMinClass = computed(() => {
  if (props.formLoading) return 'min-h-[min(40vh,20rem)]'
  if (isStaged.value || props.size === 'xl') return 'min-h-[min(45vh,22rem)]'
  if (props.size === 'md') return 'min-h-0'
  return 'min-h-0'
})

const skeletonCount = computed(() => (props.size === 'md' ? 3 : 4))
</script>

<template>
  <form
    class="flex min-h-0 flex-1 flex-col"
    :aria-busy="busy()"
    novalidate
    @submit.prevent="onPrimaryAction"
  >
    <div
      :class="cn('relative flex-1 overflow-y-auto px-6 py-4', bodyMinClass)"
    >
      <FormStepProgress
        v-if="isStaged && steps.length"
        class="mb-6"
        :steps="steps"
        :current-step="currentStep"
      />

      <FormBuilder
        :key="formKey"
        :fields="fields"
        :columns="columns"
        :visible-step-index="isStaged ? currentStep : null"
        :hide-section-legends="isStaged"
        :class="formLoading ? 'pointer-events-none opacity-40' : undefined"
      />

      <div
        v-if="formLoading"
        class="absolute inset-0 z-10 flex flex-col gap-4 bg-background/80 px-6 py-4 backdrop-blur-[1px]"
        role="status"
        aria-live="polite"
        aria-busy="true"
        aria-label="Loading form data"
      >
        <Skeleton v-for="i in skeletonCount" :key="i" class="h-10 w-full rounded-lg" />
      </div>
    </div>

    <SheetFooter class="shrink-0 gap-2 border-t border-muted/60 bg-background px-6 py-4">
      <Button
        type="button"
        variant="outline"
        :class="formButtonClass"
        @click="emit('cancel')"
      >
        {{ cancelLabel }}
      </Button>

      <Button
        v-if="isStaged && currentStep > 0"
        type="button"
        variant="outline"
        :class="formButtonClass"
        :disabled="busy()"
        @click="goBack"
      >
        <ChevronLeft class="mr-1 size-4" aria-hidden="true" />
        Back
      </Button>

      <Button
        type="submit"
        :class="formButtonClass"
        :disabled="busy()"
        :aria-busy="busy()"
      >
        <Loader2 v-if="busy()" class="mr-2 size-4 animate-spin" aria-hidden="true" />
        {{ primaryLabel }}
      </Button>
    </SheetFooter>
  </form>
</template>
