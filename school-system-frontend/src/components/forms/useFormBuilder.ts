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
  | 'time'
  | 'phone'
  | 'relation'
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
}

export function useFormBuilder<T extends z.ZodTypeAny>(schema: T, initialValues?: FormValues<T>) {
  const form = useForm({
    validationSchema: toTypedSchema(schema),
    initialValues: initialValues as never,
    validateOnMount: false,
  })

  function applyServerErrors(error: unknown) {
    const errors = getValidationErrors(error)
    Object.entries(errors).forEach(([field, messages]) => {
      form.setFieldError(field as never, messages[0])
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
