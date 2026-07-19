<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { z } from 'zod'
import FormSheetBody from '@/components/forms/FormSheetBody.vue'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  FORM_REQUIRED_DESCRIPTION,
  formSheetSizeClass,
  formSurfaceClass,
  resolveFormSheetColumns,
  resolveFormSheetSize,
  type FormSheetColumns,
  type FormSheetSize,
} from '@/lib/form-standards'
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
    /** Override auto size (derived from field count / wizard mode). */
    size?: FormSheetSize
    showRequiredHint?: boolean
    /** Override auto columns (md → 1, larger → 2). */
    columns?: FormSheetColumns
    staged?: boolean
  }>(),
  {
    saveLabel: 'Save',
    savingLabel: 'Saving…',
    cancelLabel: 'Cancel',
    showRequiredHint: true,
    formLoading: false,
  },
)

const emit = defineEmits<{ submit: [values: Record<string, unknown>] }>()

const bodyRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)
const isLoading = computed(() => props.formLoading || props.loading)

const resolvedSize = computed<FormSheetSize>(
  () => props.size ?? resolveFormSheetSize(props.fields, props.staged),
)

const resolvedColumns = computed<FormSheetColumns>(
  () => props.columns ?? resolveFormSheetColumns(resolvedSize.value),
)

defineExpose({
  applyServerErrors: (error: unknown) => bodyRef.value?.applyServerErrors(error),
})

watch(open, (isOpen) => {
  if (!isOpen) bodyRef.value = null
})
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent
      :class="cn(
        'flex max-h-[90vh] w-full flex-col gap-0 overflow-hidden rounded-xl border-muted/60 p-0 shadow-lg',
        formSurfaceClass,
        formSheetSizeClass[resolvedSize],
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
        :columns="resolvedColumns"
        :size="resolvedSize"
        :staged="staged"
        @cancel="open = false"
        @submit="(values) => emit('submit', values)"
      />
    </DialogContent>
  </Dialog>
</template>
