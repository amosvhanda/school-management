import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { shouldUseFormSteps } from '@/lib/form-steps'

export const FORM_REQUIRED_DESCRIPTION =
  'Fields marked with * are required. Validation runs before saving.'

/** Dialog width tokens for FormSheet — sized to the form, not one-size-fits-all. */
export type FormSheetSize = 'md' | 'lg' | 'xl'

export type FormSheetColumns = 1 | 2 | 3

/**
 * Shared dialog widths so every CRUD form feels the same width band.
 * md = tiny prompts only; lg = standard CRUD; xl = wizards / dense forms.
 */
export const formSheetSizeClass: Record<FormSheetSize, string> = {
  md: 'sm:max-w-lg',
  lg: 'sm:max-w-2xl',
  xl: 'sm:max-w-3xl',
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
 * Keep CRUD forms on the same width (lg) unless they are wizards or tiny prompts.
 */
export function resolveFormSheetSize(
  fields: FormFieldSchema[],
  staged?: boolean,
): FormSheetSize {
  if (shouldUseFormSteps(fields, staged)) return 'xl'
  if (fields.some((field) => field.type === 'guardian-section')) return 'xl'

  const count = countFormInputs(fields)
  // Tiny confirmations only — most create/edit sheets share `lg` so layouts match.
  if (count <= 2) return 'md'
  return 'lg'
}

/**
 * Standard application form layout is always 2 columns from `sm` up.
 * Only explicit md prompts stay single-column.
 */
export function resolveFormSheetColumns(size: FormSheetSize): FormSheetColumns {
  return size === 'md' ? 1 : 2
}

/** PreSkool-style field labels: readable sentence case above each control. */
export const formLabelClass =
  'text-sm font-medium leading-none text-foreground'

/** Fluid inputs aligned with shadcn focus rings — fixed height for alignment. */
export const formInputClass =
  'h-10 w-full focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0'

export const formTextareaClass =
  'w-full min-h-[96px] resize-y focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0'

export const formSelectTriggerClass =
  'h-10 w-full focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0'

/** Section headings inside multi-step / grouped forms. */
export const formSectionLegendClass =
  'mb-4 w-full border-b border-border/60 pb-3 text-sm font-semibold tracking-tight text-foreground'

/** Shared sheet/card header title + description. */
export const formSheetTitleClass = 'text-lg font-medium tracking-tight text-foreground'
export const formSheetDescriptionClass = 'text-sm leading-relaxed text-muted-foreground'

/** Tactile button feedback for primary actions. */
export const formButtonClass = 'transition-all duration-200 active:scale-[0.98]'

/** Surfaces match shadcn Card (soft elevation + border ring). */
export const formSurfaceClass = 'shadow-[var(--shadow-soft)] ring-1 ring-border/80'

/**
 * Responsive grid — identical gap + columns everywhere FormBuilder is used.
 * Never use ad-hoc grids for standard application forms.
 */
export function formGridClass(columns: FormSheetColumns = 2): string {
  if (columns === 1) return 'grid grid-cols-1 gap-x-4 gap-y-5'
  if (columns === 3) return 'grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2 lg:grid-cols-3'
  return 'grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2'
}

/** Reserved space so error messages do not shift layout. */
export const formMessageAreaClass = 'min-h-[1.25rem]'

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
