import type { FormFieldSchema } from '@/components/forms/useFormBuilder'

/** Fields rendered inside the guardian-section block (for step validation). */
export const GUARDIAN_STEP_FIELD_NAMES = [
  'guardianMode',
  'guardian_id',
  'guardianFirstName',
  'guardianSurname',
  'guardianPhone',
  'guardianEmail',
  'guardianRelationship',
] as const

export interface FormStep {
  id: string
  title: string
  fields: FormFieldSchema[]
  fieldNames: string[]
}

function fieldNamesInStep(fields: FormFieldSchema[]): string[] {
  const names: string[] = []
  for (const field of fields) {
    if (field.type === 'guardian-section') {
      names.push(...GUARDIAN_STEP_FIELD_NAMES)
    } else {
      names.push(field.name)
    }
  }
  return names
}

/** Group form fields into wizard steps (one per `section` value). */
export function buildFormSteps(fields: FormFieldSchema[]): FormStep[] {
  const steps: FormStep[] = []
  let currentTitle = ''
  let currentFields: FormFieldSchema[] = []

  function flush() {
    if (!currentFields.length) return
    steps.push({
      id: currentTitle || `step-${steps.length}`,
      title: currentTitle || `Step ${steps.length + 1}`,
      fields: currentFields,
      fieldNames: fieldNamesInStep(currentFields),
    })
  }

  for (const field of fields) {
    const section = field.section ?? ''
    if (section !== currentTitle) {
      flush()
      currentTitle = section
      currentFields = [field]
    } else {
      currentFields.push(field)
    }
  }
  flush()

  return steps
}

export function shouldUseFormSteps(fields: FormFieldSchema[], staged?: boolean): boolean {
  if (staged === false) return false
  if (staged === true) return buildFormSteps(fields).length >= 2
  return buildFormSteps(fields).length >= 3
}
