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
  description: string
  icon: Component
  /** Registry list key when this tab embeds a CRUD section. */
  listKey?: string
  progressKey?: 'profile' | 'terms' | 'classes' | 'subjects' | 'rooms' | 'fees'
}

export const SETUP_TABS: SetupTab[] = [
  {
    id: 'profile',
    title: 'School Profile',
    description:
      'Basic information about your school that defines your institution and powers invoices, communications, and public-facing school details.',
    icon: School,
    progressKey: 'profile',
  },
  {
    id: 'academic-setup',
    title: 'Academic Setup',
    description:
      'Create academic terms and mark the current term used by gradebook, exams, attendance, and reports.',
    icon: CalendarDays,
    listKey: 'academics-terms',
    progressKey: 'terms',
  },
  {
    id: 'classes',
    title: 'Classes',
    description:
      'Set up class groups, grade levels, streams, and class teachers for enrollment and teaching.',
    icon: GraduationCap,
    listKey: 'academics-setup',
    progressKey: 'classes',
  },
  {
    id: 'rooms',
    title: 'Rooms',
    description:
      'Manage classrooms and specialist spaces used for timetable scheduling and capacity planning.',
    icon: DoorOpen,
    listKey: 'academics-rooms',
    progressKey: 'rooms',
  },
  {
    id: 'subjects',
    title: 'Subjects',
    description:
      'Maintain the subject catalogue taught across grade levels, streams, and teacher assignments.',
    icon: BookMarked,
    listKey: 'academics-subjects',
    progressKey: 'subjects',
  },
  {
    id: 'fees',
    title: 'Fee Structures',
    description:
      'Define fee amounts by class and category so invoices and student accounts stay consistent.',
    icon: Tags,
    listKey: 'finance-fees',
    progressKey: 'fees',
  },
]

export function tabById(id: SetupTabId): SetupTab {
  return SETUP_TABS.find((tab) => tab.id === id) ?? SETUP_TABS[0]
}
