import type { Component } from 'vue'
import {
  Award,
  BookOpen,
  CalendarDays,
  ClipboardList,
  FileText,
  GraduationCap,
  HeartPulse,
  History,
  Library,
  Lock,
  MessageSquare,
  ScrollText,
  ShieldAlert,
  UserRound,
  Wallet,
} from '@lucide/vue'

export type StudentProfileTabId =
  | 'overview'
  | 'attendance'
  | 'academics'
  | 'exams'
  | 'fees'
  | 'timetable'
  | 'health'
  | 'library'
  | 'documents'
  | 'timeline'
  | 'achievements'
  | 'discipline'
  | 'communication'
  | 'notes'
  | 'audit'

export type StudentProfileTab = {
  id: StudentProfileTabId
  label: string
  icon: Component
  locked?: boolean
}

/** Primary profile rail — matches PreSkool student detail mockup. */
export const STUDENT_PROFILE_PRIMARY_TAB_IDS: StudentProfileTabId[] = [
  'overview',
  'attendance',
  'academics',
  'exams',
  'fees',
]

export const STUDENT_PROFILE_TABS: StudentProfileTab[] = [
  { id: 'overview', label: 'Overview', icon: UserRound },
  { id: 'attendance', label: 'Attendance', icon: CalendarDays },
  { id: 'academics', label: 'Academics', icon: GraduationCap },
  { id: 'exams', label: 'Exams & Results', icon: ClipboardList },
  { id: 'fees', label: 'Fees', icon: Wallet },
  { id: 'timetable', label: 'Timetable', icon: BookOpen },
  { id: 'health', label: 'Health', icon: HeartPulse },
  { id: 'library', label: 'Library', icon: Library },
  { id: 'documents', label: 'Documents', icon: FileText },
  { id: 'timeline', label: 'Timeline', icon: History },
  { id: 'achievements', label: 'Achievements', icon: Award },
  { id: 'discipline', label: 'Discipline', icon: ShieldAlert },
  { id: 'communication', label: 'Communication', icon: MessageSquare },
  { id: 'notes', label: 'Notes', icon: Lock, locked: true },
  { id: 'audit', label: 'Audit Trail', icon: ScrollText },
]

/**
 * Profile tab triggers — must beat TabsTrigger defaults (`h-[calc(100%-1px)]`, `flex-1`, line `after`).
 * Prefix `!` so tailwind-merge keeps the override.
 */
export const profileTabTriggerClass =
  '!h-auto !min-h-9 !flex-none !shrink-0 !self-start items-center gap-1.5 rounded-xl border border-transparent px-3 py-2 text-xs font-medium text-muted-foreground after:!hidden data-active:border-primary/40 data-active:bg-primary/10 data-active:text-primary sm:text-sm'

export function studentProfilePrimaryTabs(): StudentProfileTab[] {
  const primary = new Set(STUDENT_PROFILE_PRIMARY_TAB_IDS)
  return STUDENT_PROFILE_TABS.filter((tab) => primary.has(tab.id))
}

export function studentProfileMoreTabs(): StudentProfileTab[] {
  const primary = new Set(STUDENT_PROFILE_PRIMARY_TAB_IDS)
  return STUDENT_PROFILE_TABS.filter((tab) => !primary.has(tab.id))
}
