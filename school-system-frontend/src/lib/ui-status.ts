/**
 * Shared semantic status colors — use theme tokens instead of one-off Tailwind hues.
 */

export type StatusTone = 'success' | 'warning' | 'info' | 'danger' | 'neutral' | 'accent'

export const STATUS_TONE_ICON: Record<StatusTone, string> = {
  success: 'bg-chart-2/15 text-chart-2',
  warning: 'bg-chart-3/15 text-chart-3',
  info: 'bg-chart-4/15 text-chart-4',
  danger: 'bg-destructive/15 text-destructive',
  neutral: 'bg-muted text-muted-foreground',
  accent: 'bg-primary/10 text-primary',
}

export const STATUS_TONE_BADGE: Record<StatusTone, string> = {
  success: 'border-chart-2/30 bg-chart-2/10 text-chart-2',
  warning: 'border-chart-3/30 bg-chart-3/10 text-chart-3',
  info: 'border-chart-4/30 bg-chart-4/10 text-chart-4',
  danger: 'border-destructive/30 bg-destructive/10 text-destructive',
  neutral: 'border-border bg-muted/40 text-muted-foreground',
  accent: 'border-primary/30 bg-primary/10 text-primary',
}

export const STATUS_TONE_DOT: Record<StatusTone, string> = {
  success: 'bg-chart-2',
  warning: 'bg-chart-3',
  info: 'bg-chart-4',
  danger: 'bg-destructive',
  neutral: 'bg-muted-foreground',
  accent: 'bg-primary',
}

export const STATUS_TONE_SOLID: Record<StatusTone, string> = {
  success: 'border-chart-2 bg-chart-2 text-primary-foreground hover:bg-chart-2/90',
  warning: 'border-chart-3 bg-chart-3 text-primary-foreground hover:bg-chart-3/90',
  info: 'border-chart-4 bg-chart-4 text-primary-foreground hover:bg-chart-4/90',
  danger: 'border-destructive bg-destructive text-destructive-foreground hover:bg-destructive/90',
  neutral: 'border-foreground bg-foreground text-background hover:bg-foreground/90',
  accent: 'border-primary bg-primary text-primary-foreground hover:bg-primary/90',
}

export type AttendanceMarkStatus =
  | 'present'
  | 'absent'
  | 'late'
  | 'excused'
  | 'sick'
  | 'left_early'
  | 'unmarked'

export const ATTENDANCE_STATUS_TONE: Record<AttendanceMarkStatus, StatusTone> = {
  present: 'success',
  late: 'warning',
  excused: 'info',
  sick: 'accent',
  left_early: 'info',
  absent: 'danger',
  unmarked: 'neutral',
}

export function gradeLetterTone(letter: string): StatusTone {
  const g = letter.trim().toUpperCase()
  if (g.startsWith('A')) return 'success'
  if (g.startsWith('B')) return 'info'
  if (g.startsWith('C')) return 'warning'
  if (g.startsWith('D') || g.startsWith('E') || g.startsWith('F')) return 'danger'
  return 'neutral'
}

export function assignmentTypeTone(type: string): StatusTone {
  const t = type.toLowerCase()
  if (t === 'test') return 'danger'
  if (t === 'project') return 'success'
  return 'info'
}

export function lessonStatusTone(status: string): StatusTone {
  if (status === 'completed') return 'success'
  if (status === 'current') return 'info'
  return 'neutral'
}
