import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { shouldUseFormSteps } from '@/lib/form-steps'

export const FORM_REQUIRED_DESCRIPTION =
  'Fields marked with * are required. Validation runs before saving.'

/** Dialog width tokens for FormSheet — sized to the form, not one-size-fits-all. */
export type FormSheetSize = 'md' | 'lg' | 'xl'

export type FormSheetColumns = 1 | 2 | 3

export const formSheetSizeClass: Record<FormSheetSize, string> = {
  md: 'sm:max-w-md',
  lg: 'sm:max-w-xl',
  xl: 'sm:max-w-2xl',
}

/** Count interactive inputs (guardian block ≈ several fields). */
export function countFormInputs(fields: FormFieldSchema[]): number {
  let count = 0
  for (const field of fields) {
    count += field.type === 'guardian-section' ? 6 : 1
  }
  return count
}

/**
 * Pick dialog width from form complexity.
 * Short → md, typical CRUD → lg, wizards / dense forms → xl.
 */
export function resolveFormSheetSize(
  fields: FormFieldSchema[],
  staged?: boolean,
): FormSheetSize {
  if (shouldUseFormSteps(fields, staged)) return 'xl'
  if (fields.some((field) => field.type === 'guardian-section')) return 'xl'

  const count = countFormInputs(fields)
  if (count <= 4) return 'md'
  if (count <= 12) return 'lg'
  return 'xl'
}

/** Narrow dialogs use a single column so fields are not cramped. */
export function resolveFormSheetColumns(size: FormSheetSize): FormSheetColumns {
  return size === 'md' ? 1 : 2
}

/** Premium micro-typography for form labels (Stripe / Linear style). */
export const formLabelClass =
  'text-xs font-semibold uppercase tracking-wider text-muted-foreground'

/** Fluid inputs with soft borders and refined focus rings. */
export const formInputClass =
  'w-full border-muted/60 shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0'

export const formTextareaClass =
  'w-full min-h-[88px] resize-y border-muted/60 shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0'

export const formSelectTriggerClass =
  'w-full border-muted/60 shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0'

/** Section headings inside multi-step / grouped forms. */
export const formSectionLegendClass =
  'mb-4 w-full border-b border-muted/60 pb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground'

/** Tactile button feedback for primary actions. */
export const formButtonClass = 'transition-all duration-200 active:scale-[0.98]'

/** Premium card / panel surfaces. */
export const formSurfaceClass = 'border-muted/60 shadow-sm'

/** Responsive grid — never fixed widths on fields. */
export function formGridClass(columns: FormSheetColumns = 2): string {
  if (columns === 1) return 'grid grid-cols-1 gap-4'
  if (columns === 3) return 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3'
  return 'grid grid-cols-1 gap-4 sm:grid-cols-2'
}

/** Reserved space so error messages do not shift layout. */
export const formMessageAreaClass = 'min-h-[1.125rem]'

export const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
]

type FieldInput = Omit<FormFieldSchema, 'section' | 'colSpan'> & { colSpan?: 1 | 2 }

/** Build a consistent field group for FormBuilder (section + default colSpan). */
export function formSection(section: string, fields: FieldInput[]): FormFieldSchema[] {
  return fields.map((field) => ({
    colSpan: 1,
    ...field,
    section,
  }))
}

/** Smooth height transitions for fields, sections, and validation messages. */
export const formAnimateClass = 'motion-safe:transition-[height,opacity] motion-safe:duration-200'

/** Wrapper for animated field groups (use with v-auto-animate). */
export const formFieldsAnimateOptions = { duration: 220, easing: 'ease-out' } as const

export function mergeFormSections(...groups: FormFieldSchema[][]): FormFieldSchema[] {
  return groups.flat()
}
