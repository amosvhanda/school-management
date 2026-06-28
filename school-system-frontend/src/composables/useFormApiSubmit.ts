import type { z } from 'zod'
import { useFormBuilder, type FormValues } from '@/components/forms/useFormBuilder'
import { getErrorMessage } from '@/lib/api-response'

export interface FormApiSubmitOptions<T extends z.ZodTypeAny> {
  schema: T
  initialValues?: z.infer<T>
  /** Axios-backed API call; validation errors are mapped to fields automatically. */
  onSubmit: (values: z.infer<T>) => Promise<void>
  onSuccess?: (values: z.infer<T>) => void | Promise<void>
  onError?: (message: string, error: unknown) => void
}

/**
 * Standard form submit pipeline:
 * Zod (via @vee-validate/zod) → vee-validate → axios API → field errors.
 */
export function useFormApiSubmit<T extends z.ZodTypeAny>(options: FormApiSubmitOptions<T>) {
  const form = useFormBuilder(options.schema, options.initialValues)

  const submit = form.handleSubmit(async (values) => {
    try {
      await options.onSubmit(values as z.infer<T>)
      await options.onSuccess?.(values as z.infer<T>)
    } catch (error) {
      form.applyServerErrors(error)
      options.onError?.(getErrorMessage(error), error)
    }
  })

  /** Use when a child form component already validated and emits values (FormCard / FormSheet). */
  async function submitValues(values: FormValues<T> | Record<string, unknown>) {
    try {
      await options.onSubmit(values as FormValues<T>)
      await options.onSuccess?.(values as FormValues<T>)
    } catch (error) {
      form.applyServerErrors(error)
      options.onError?.(getErrorMessage(error), error)
    }
  }

  return { ...form, submit, submitValues }
}
