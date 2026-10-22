<script setup lang="ts">
import { computed, ref } from 'vue'
import type { z } from 'zod'
import FormCardBody from '@/components/forms/FormCardBody.vue'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  FORM_REQUIRED_DESCRIPTION,
  formSheetDescriptionClass,
  formSheetTitleClass,
  resolveFormSheetColumns,
  resolveFormSheetSize,
} from '@/lib/form-standards'

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
    saveLabel?: string
    savingLabel?: string
    showRequiredHint?: boolean
    columns?: 1 | 2 | 3
  }>(),
  {
    saveLabel: 'Save',
    savingLabel: 'Saving…',
    showRequiredHint: true,
    formLoading: false,
  },
)

const resolvedColumns = computed(
  () => props.columns ?? resolveFormSheetColumns(resolveFormSheetSize(props.fields)),
)

const emit = defineEmits<{ submit: [values: Record<string, unknown>] }>()

const bodyRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

defineExpose({
  applyServerErrors: (error: unknown) => bodyRef.value?.applyServerErrors(error),
})
</script>

<template>
  <Card class="w-full overflow-hidden py-0 shadow-[var(--shadow-soft)] ring-1 ring-border/70">
    <CardHeader class="space-y-1.5 border-b border-border/60 px-6 pt-6 pb-4">
      <CardTitle :class="formSheetTitleClass">
        {{ title }}
      </CardTitle>
      <CardDescription
        v-if="showRequiredHint || description"
        :class="formSheetDescriptionClass"
      >
        {{ description ?? FORM_REQUIRED_DESCRIPTION }}
      </CardDescription>
    </CardHeader>
    <CardContent class="px-0 pt-0 pb-0">
      <FormCardBody
        ref="bodyRef"
        :fields="fields"
        :schema="schema"
        :form-key="formKey"
        :reset-values="resetValues"
        :form-loading="formLoading"
        :saving="saving"
        :save-label="saveLabel"
        :saving-label="savingLabel"
        :columns="resolvedColumns"
        @submit="(values) => emit('submit', values)"
      />
    </CardContent>
  </Card>
</template>
