import { computed, toValue, watch, type MaybeRefOrGetter } from 'vue'
import { useFormContext, useFormValues, useSetFormValues } from 'vee-validate'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'

export interface FormCascadeRule {
  childField: string
  parentField: string
}

function buildCascadeRules(fields: FormFieldSchema[]): FormCascadeRule[] {
  return fields.flatMap((field) => {
    if (field.type !== 'relation' || !field.relation?.dependsOn) return []
    if (field.relation.dependsOn.clearOnChange === false) return []
    return [{
      childField: field.name,
      parentField: field.relation.dependsOn.field,
    }]
  })
}

/**
 * When a parent relation field changes, reset dependent child fields to prevent stale IDs in payloads.
 * Complements RelationSelect dependsOn clearing at the component level.
 */
export function useFormCascade(fields: MaybeRefOrGetter<FormFieldSchema[]>) {
  const form = useFormContext()
  const values = useFormValues()
  const setValues = useSetFormValues()

  const rules = computed(() => buildCascadeRules(toValue(fields)))

  watch(
    values,
    (next, previous) => {
      if (!previous || !form || !rules.value.length) return

      const patch: Record<string, string> = {}

      for (const rule of rules.value) {
        const nextParent = next?.[rule.parentField]
        const prevParent = previous?.[rule.parentField]
        if (nextParent !== prevParent && next?.[rule.childField]) {
          patch[rule.childField] = ''
        }
      }

      if (Object.keys(patch).length) {
        setValues(patch, false)
      }
    },
    { deep: true },
  )

  return { rules }
}
