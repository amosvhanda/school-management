import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection } from '@/lib/form-standards'
import { formatTime } from '@/lib/format'
import { isValidIsoDate } from '@/lib/validation'
import { moduleEndpoints } from '@/services'

function extractClockTime(value: unknown): string {
  if (value == null || value === '') return ''
  const raw = String(value).trim()
  if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(raw)) {
    const [hh, mm] = raw.split(':')
    return `${hh.padStart(2, '0')}:${mm}`
  }
  const formatted = formatTime(raw, '')
  return formatted || ''
}

export const examFormSchema = z.object({
  name: z.string().trim().min(1, 'Exam name is required'),
  term_id: z.string().min(1, 'Term is required'),
  grade_level_id: z.string().min(1, 'Grade level is required'),
  subject_id: z.string().min(1, 'Subject is required'),
  exam_date: z
    .string()
    .min(1, 'Exam date is required')
    .refine(isValidIsoDate, 'Enter a valid date'),
  academic_year: z.string().trim().min(1, 'Academic year is required'),
  total_marks: z.coerce.number({ invalid_type_error: 'Total marks is required' }).min(1, 'Total marks must be at least 1'),
  passing_marks: z.coerce.number().min(0).optional().or(z.literal('')),
  start_time: z.string().optional().or(z.literal('')),
  end_time: z.string().optional().or(z.literal('')),
  description: z.string().optional().or(z.literal('')),
})

export const examFormFields: FormFieldSchema[] = [
  ...formSection('Exam details', [
    { name: 'name', label: 'Exam name', type: 'text', required: true, colSpan: 2, placeholder: 'Mid-year Mathematics' },
    {
      name: 'term_id',
      label: 'Term',
      type: 'relation',
      required: true,
      placeholder: 'Select term',
      relation: { endpoint: moduleEndpoints.terms, moduleLabel: 'term' },
    },
    {
      name: 'grade_level_id',
      label: 'Grade level',
      type: 'relation',
      required: true,
      placeholder: 'Select grade',
      relation: { endpoint: moduleEndpoints.gradeLevels, moduleLabel: 'grade level' },
    },
    {
      name: 'subject_id',
      label: 'Subject',
      type: 'relation',
      required: true,
      placeholder: 'Select subject',
      relation: { endpoint: moduleEndpoints.subjects, moduleLabel: 'subject' },
    },
    { name: 'exam_date', label: 'Exam date', type: 'date', required: true },
    { name: 'academic_year', label: 'Academic year', type: 'text', required: true, placeholder: '2026' },
    { name: 'start_time', label: 'Start time', type: 'time', placeholder: '08:00' },
    { name: 'end_time', label: 'End time', type: 'time', placeholder: '10:00' },
  ]),
  ...formSection('Marking', [
    { name: 'total_marks', label: 'Total marks', type: 'number', required: true },
    { name: 'passing_marks', label: 'Pass mark', type: 'number', placeholder: 'Optional' },
    { name: 'description', label: 'Instructions / notes', type: 'textarea', colSpan: 2, placeholder: 'Calculator allowed, Section A & B…' },
  ]),
]

export function mapExamRowToFormValues(row: Record<string, unknown>): Record<string, unknown> {
  const relId = (key: string, nested: string) => {
    if (row[key] != null) return String(row[key])
    const obj = row[nested] as { id?: number } | undefined
    return obj?.id != null ? String(obj.id) : ''
  }

  return {
    name: String(row.name ?? ''),
    term_id: relId('term_id', 'term'),
    grade_level_id: relId('grade_level_id', 'grade_level'),
    subject_id: relId('subject_id', 'subject'),
    exam_date: String(row.exam_date ?? '').slice(0, 10),
    academic_year: String(row.academic_year ?? new Date().getFullYear()),
    total_marks: Number(row.total_marks ?? 100),
    passing_marks: row.passing_marks != null ? Number(row.passing_marks) : '',
    start_time: extractClockTime(row.start_time),
    end_time: extractClockTime(row.end_time),
    description: String(row.description ?? ''),
  }
}

export function mapExamFormToPayload(values: Record<string, unknown>): Record<string, unknown> {
  const payload: Record<string, unknown> = {
    name: values.name,
    term_id: Number(values.term_id),
    grade_level_id: Number(values.grade_level_id),
    subject_id: Number(values.subject_id),
    exam_date: values.exam_date,
    academic_year: values.academic_year,
    total_marks: Number(values.total_marks),
    description: values.description || undefined,
  }

  if (values.passing_marks !== '' && values.passing_marks != null) {
    payload.passing_marks = Number(values.passing_marks)
  }
  if (values.start_time) payload.start_time = values.start_time
  if (values.end_time) payload.end_time = values.end_time

  return payload
}

export function examCreateDefaults(): Record<string, unknown> {
  return {
    name: '',
    term_id: '',
    grade_level_id: '',
    subject_id: '',
    exam_date: '',
    academic_year: String(new Date().getFullYear()),
    total_marks: 100,
    passing_marks: '',
    start_time: '',
    end_time: '',
    description: '',
  }
}
