import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import type { z } from 'zod'
import { getValidationErrors } from '@/lib/api-response'

export type FormValues<T extends z.ZodTypeAny> = z.infer<T>

export interface RelationDependsOn {
  /** Parent form field that must be set before this relation is enabled */
  field: string
  /** Query param name sent when loading options (defaults to parent field name) */
  paramKey?: string
  /** Clear this field when the parent value changes (default: true) */
  clearOnChange?: boolean
}

export interface RelationFieldConfig {
  endpoint: string
  rowKey?: string
  fallbackRowKey?: string
  queryParams?: Record<string, unknown>
  params?: Record<string, unknown>
  /** Route to open create flow, e.g. `/guardians?create=1` */
  createRoute?: string
  /** Human label for empty states, e.g. `guardian`, `student` */
  moduleLabel?: string
  dependsOn?: RelationDependsOn
  /**
   * When an option is selected, copy its label into this form field
   * only if that field is currently empty (e.g. grade level → form label).
   */
  fillEmptyField?: string
}

export interface ClassNameDeriveConfig {
  /** Grade level relation field, e.g. `grade_level_id`. */
  levelField: string
  /** Stream relation field, e.g. `stream_id`. */
  streamField: string
  /** Optional form label field to mirror the grade level name. */
  formField?: string
}

export type FormFieldType =
  | 'text'
  | 'email'
  | 'password'
  | 'number'
  | 'textarea'
  | 'select'
  | 'checkbox'
  | 'date'
  | 'datetime-local'
  | 'time'
  | 'phone'
  | 'relation'
  | 'multirelation'
  | 'guardian-section'

export interface FormFieldSchema {
  name: string
  label: string
  type: FormFieldType
  placeholder?: string
  description?: string
  required?: boolean
  section?: string
  colSpan?: 1 | 2
  options?: Array<{ label: string; value: string }>
  min?: string
  max?: string
  disabled?: boolean
  rowKey?: string
  payloadPath?: string
  relation?: RelationFieldConfig
  /**
   * On the class `name` field: auto-build display name from grade level + stream
   * (Form 4A2 vs Lower 6 Commercials).
   */
  deriveClassName?: ClassNameDeriveConfig
}

export function useFormBuilder<T extends z.ZodTypeAny>(schema: T, initialValues?: FormValues<T>) {
  const form = useForm({
    validationSchema: toTypedSchema(schema),
    initialValues: initialValues as never,
    validateOnMount: false,
    /** Staged forms hide fields per step; keep values when those fields unmount. */
    keepValuesOnUnmount: true,
  })

  function applyServerErrors(error: unknown, fieldAliases?: Record<string, string>) {
    const errors = getValidationErrors(error)
    Object.entries(errors).forEach(([field, messages]) => {
      const mappedField = fieldAliases?.[field] ?? field
      form.setFieldError(mappedField as never, messages[0])
    })
  }

  /** Map Laravel / axios 422 validation payload onto vee-validate field errors. */
  function applyAxiosErrors(error: unknown) {
    applyServerErrors(error)
  }

  /**
   * Wrap an async API submit handler with Zod validation.
   * Usage: `const onSubmit = handleSubmit(submitToApi)`
   */
  function createSubmitHandler(
    handler: (values: z.infer<T>) => Promise<void> | void,
  ) {
    return form.handleSubmit(async (values) => {
      await handler(values as z.infer<T>)
    })
  }

  return { ...form, applyServerErrors, applyAxiosErrors, createSubmitHandler }
}
