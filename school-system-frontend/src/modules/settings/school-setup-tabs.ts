import type { Component } from 'vue'
import {
  BookMarked,
  CalendarDays,
  DoorOpen,
  GraduationCap,
  School,
  Tags,
} from '@lucide/vue'

export type SetupTabId =
  | 'profile'
  | 'academic-setup'
  | 'classes'
  | 'rooms'
  | 'subjects'
  | 'fees'

export interface SetupTab {
  id: SetupTabId
  title: string
  icon: Component
  /** Registry list key when this tab embeds a CRUD section. */
  listKey?: string
  progressKey?: 'profile' | 'terms' | 'classes' | 'subjects'
}

export const SETUP_TABS: SetupTab[] = [
  { id: 'profile', title: 'School Profile', icon: School, progressKey: 'profile' },
  {
    id: 'academic-setup',
    title: 'Academic Setup',
    icon: CalendarDays,
    listKey: 'academics-terms',
    progressKey: 'terms',
  },
  {
    id: 'classes',
    title: 'Classes',
    icon: GraduationCap,
    listKey: 'academics-setup',
    progressKey: 'classes',
  },
  {
    id: 'rooms',
    title: 'Rooms',
    icon: DoorOpen,
    listKey: 'academics-rooms',
  },
  {
    id: 'subjects',
    title: 'Subjects',
    icon: BookMarked,
    listKey: 'academics-subjects',
    progressKey: 'subjects',
  },
  {
    id: 'fees',
    title: 'Fee Structures',
    icon: Tags,
    listKey: 'finance-fees',
  },
]

export function tabById(id: SetupTabId): SetupTab {
  return SETUP_TABS.find((tab) => tab.id === id) ?? SETUP_TABS[0]
}
