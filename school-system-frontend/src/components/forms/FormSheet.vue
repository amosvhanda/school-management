<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { z } from 'zod'
import FormSheetBody from '@/components/forms/FormSheetBody.vue'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
// Swapped Sheet imports for Dialog components
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { FORM_REQUIRED_DESCRIPTION, formSurfaceClass } from '@/lib/form-standards'
import { cn } from '@/lib/utils'

const open = defineModel<boolean>('open', { required: true })

const props = withDefaults(
  defineProps<{
    title: string
    description?: string
    fields: FormFieldSchema[]
    schema: z.ZodTypeAny
    formKey?: string
    resetValues?: Record<string, unknown>
    saving?: boolean
    formLoading?: boolean
    loading?: boolean
    saveLabel?: string
    savingLabel?: string
    cancelLabel?: string
    size?: 'md' | 'lg' | 'xl'
    showRequiredHint?: boolean
    columns?: 2 | 3
    staged?: boolean
  }>(),
  {
    saveLabel: 'Save',
    savingLabel: 'Saving…',
    cancelLabel: 'Cancel',
    size: 'xl',
    showRequiredHint: true,
    columns: 2,
    formLoading: false,
  },
)

const emit = defineEmits<{ submit: [values: Record<string, unknown>] }>()

const bodyRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)
const isLoading = computed(() => props.formLoading || props.loading)

defineExpose({
  applyServerErrors: (error: unknown) => bodyRef.value?.applyServerErrors(error),
})

watch(open, (isOpen) => {
  if (!isOpen) bodyRef.value = null
})

// Adjusted widths to fit floating modal constraints beautifully
const sizeClass = {
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-2xl',
} as const
</script>

<template>
  <Dialog v-model:open="open">
    <!-- DialogContent handles the absolute viewport centering automatically -->
    <DialogContent
      :class="cn(
        'flex max-h-[90vh] w-full flex-col gap-0 overflow-hidden p-0 border-muted/60 shadow-lg rounded-xl',
        formSurfaceClass,
        sizeClass[size],
      )"
    >
      <DialogHeader class="shrink-0 space-y-1 border-b border-muted/60 px-6 pb-4 pt-6">
        <DialogTitle class="text-base font-semibold tracking-tight">
          {{ title }}
        </DialogTitle>
        <DialogDescription v-if="(showRequiredHint && !staged) || description" class="text-xs leading-relaxed">
          <slot name="description">
            {{ description ?? FORM_REQUIRED_DESCRIPTION }}
          </slot>
        </DialogDescription>
      </DialogHeader>

      <!-- Internal body container handles the scrolling fields gracefully -->
      <FormSheetBody
        v-if="open"
        ref="bodyRef"
        :fields="fields"
        :schema="schema"
        :form-key="formKey"
        :reset-values="resetValues"
        :form-loading="isLoading"
        :saving="saving"
        :save-label="saveLabel"
        :saving-label="savingLabel"
        :cancel-label="cancelLabel"
        :columns="columns"
        :staged="staged"
        @cancel="open = false"
        @submit="(values) => emit('submit', values)"
      />
    </DialogContent>
  </Dialog>
</template>
