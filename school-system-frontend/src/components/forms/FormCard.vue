<script setup lang="ts">
import { ref } from 'vue'
import type { z } from 'zod'
import FormCardBody from '@/components/forms/FormCardBody.vue'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FORM_REQUIRED_DESCRIPTION, formSurfaceClass } from '@/lib/form-standards'
import { cn } from '@/lib/utils'

withDefaults(
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
    columns: 2,
    formLoading: false,
  },
)

const emit = defineEmits<{ submit: [values: Record<string, unknown>] }>()

const bodyRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

defineExpose({
  applyServerErrors: (error: unknown) => bodyRef.value?.applyServerErrors(error),
})
</script>

<template>
  <Card :class="cn('w-full', formSurfaceClass)">
    <CardHeader class="border-b border-muted/60 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">
        {{ title }}
      </CardTitle>
      <CardDescription v-if="showRequiredHint || description" class="text-xs leading-relaxed">
        {{ description ?? FORM_REQUIRED_DESCRIPTION }}
      </CardDescription>
    </CardHeader>
    <CardContent class="pt-6">
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
        :columns="columns"
        @submit="(values) => emit('submit', values)"
      />
    </CardContent>
  </Card>
</template>
