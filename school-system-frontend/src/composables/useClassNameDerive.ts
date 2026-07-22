import { computed, toValue, watch, type MaybeRefOrGetter } from 'vue'
import { useFormContext, useFormValues } from 'vee-validate'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { resolveClassNamingParts } from '@/lib/class-naming'

/**
 * When a class-name field declares `deriveClassName`, keep `name` and optional
 * form label in sync with grade level + stream selections.
 */
export function useClassNameDerive(fields: MaybeRefOrGetter<FormFieldSchema[]>) {
  const form = useFormContext()
  const values = useFormValues()

  const config = computed(() => {
    const nameField = toValue(fields).find((field) => field.deriveClassName)
    return nameField?.deriveClassName
      ? { nameField: nameField.name, ...nameField.deriveClassName }
      : null
  })

  watch(
    () => {
      const rule = config.value
      if (!rule) return null
      const current = values.value as Record<string, unknown> | undefined
      return {
        levelId: current?.[rule.levelField],
        streamId: current?.[rule.streamField],
        nameField: rule.nameField,
        formField: rule.formField,
      }
    },
    (next) => {
      if (!next || !form || !config.value) return

      const { className, formLabel } = resolveClassNamingParts(next.levelId, next.streamId)

      if (className) {
        form.setFieldValue(next.nameField, className)
      } else if (!next.levelId) {
        // Clear auto name only when level is cleared; keep manual edits if somehow empty level
        form.setFieldValue(next.nameField, '')
      }

      if (next.formField) {
        form.setFieldValue(next.formField, formLabel)
      }
    },
    { deep: true },
  )
}
