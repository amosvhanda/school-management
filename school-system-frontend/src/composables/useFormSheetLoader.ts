import { nextTick, ref } from 'vue'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { preloadRelationFields } from '@/lib/relation-options'
import { mapRowToFormValues } from '@/modules/shared/crud-mappers'
import { fetchOne } from '@/services/dashboard.service'

export interface FormSheetLoaderOptions {
  endpoint: string
  formFields?: FormFieldSchema[]
  listKey?: string
  /** Called with mapped form values after preload / fetch (replaces resetForm for portaled sheets). */
  setFormValues?: (values: Record<string, unknown> | undefined) => void
  mapRowToValues?: (row: Record<string, unknown>) => Record<string, unknown>
  createDefaults?: Record<string, unknown> | (() => Record<string, unknown>)
  idKey?: string
}

export function useFormSheetLoader(
  options: FormSheetLoaderOptions | (() => FormSheetLoaderOptions),
) {
  const formLoading = ref(false)

  function getOptions(): FormSheetLoaderOptions {
    return typeof options === 'function' ? options() : options
  }

  async function prepareCreate(): Promise<void> {
    const opts = getOptions()
    formLoading.value = true
    try {
      if (opts.formFields?.length) {
        try {
          await preloadRelationFields(opts.formFields)
        } catch {
          // Relation preload is best-effort; fields still render with empty selects.
        }
      }
      const defaults = typeof opts.createDefaults === 'function'
        ? opts.createDefaults()
        : opts.createDefaults
      await nextTick()
      opts.setFormValues?.(defaults ?? {})
    } finally {
      formLoading.value = false
    }
  }

  async function prepareEdit(row: Record<string, unknown>): Promise<Record<string, unknown>> {
    const opts = getOptions()
    const idKey = opts.idKey ?? 'id'
    formLoading.value = true

    let record = row
    const rowId = row[idKey]
    if (rowId != null && opts.endpoint) {
      try {
        record = await fetchOne<Record<string, unknown>>(`${opts.endpoint}/${rowId}`)
      } catch {
        // fall back to list row data
      }
    }

    try {
      if (opts.formFields?.length) {
        await preloadRelationFields(opts.formFields)
      }
      await nextTick()
      const values = opts.listKey
        ? mapRowToFormValues(opts.listKey, record, opts.formFields ?? [])
        : opts.mapRowToValues?.(record) ?? record
      opts.setFormValues?.(values as Record<string, unknown>)
      return record
    } finally {
      formLoading.value = false
    }
  }

  return { formLoading, prepareCreate, prepareEdit }
}
