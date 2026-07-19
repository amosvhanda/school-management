<script setup lang="ts">
import { nextTick, watch } from 'vue'
import type { z } from 'zod'
import { Loader2 } from '@lucide/vue'
import FormBuilder from '@/components/forms/FormBuilder.vue'
import { useFormBuilder } from '@/components/forms/useFormBuilder'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { formButtonClass, formFieldsAnimateOptions } from '@/lib/form-standards'
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
    columns?: 1 | 2 | 3
    showSubmit?: boolean
  }>(),
  {
    saveLabel: 'Save',
    savingLabel: 'Saving…',
    columns: 2,
    formLoading: false,
    showSubmit: true,
  },
)

const emit = defineEmits<{ submit: [values: Record<string, unknown>] }>()

const { handleSubmit, resetForm, applyServerErrors, isSubmitting } = useFormBuilder(props.schema)

defineExpose({ applyServerErrors })

function applyResetValues(values?: Record<string, unknown>) {
  resetForm({ values: (values ?? {}) as never })
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

const busy = () => props.saving || props.formLoading || isSubmitting.value
</script>

<template>
  <form
    class="w-full space-y-6"
    :aria-busy="busy()"
    novalidate
    @submit.prevent="onSubmit"
  >
    <div
      v-auto-animate="formFieldsAnimateOptions"
      class="relative w-full"
      :class="formLoading ? 'pointer-events-none opacity-40' : undefined"
    >
      <FormBuilder :key="formKey" :fields="fields" :columns="columns" />

      <div
        v-if="formLoading"
        class="absolute inset-0 z-10 flex flex-col gap-4 rounded-lg bg-background/80 py-2 backdrop-blur-[1px]"
        role="status"
        aria-live="polite"
        aria-busy="true"
        aria-label="Loading form data"
      >
        <Skeleton v-for="i in 4" :key="i" class="h-10 w-full rounded-lg" />
      </div>
    </div>

    <Button
      v-if="showSubmit"
      type="submit"
      :class="cn(formButtonClass, 'min-w-[8rem]')"
      :disabled="busy()"
      :aria-busy="busy()"
    >
      <Loader2 v-if="busy()" class="mr-2 size-4 animate-spin" aria-hidden="true" />
      {{ busy() ? savingLabel : saveLabel }}
    </Button>
  </form>
</template>
