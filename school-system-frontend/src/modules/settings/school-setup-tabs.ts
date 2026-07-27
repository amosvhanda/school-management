import type { Component } from 'vue'
import {
  Bell,
  BookMarked,
  Building2,
  CalendarDays,
  Coins,
  DoorOpen,
  GitBranch,
  GraduationCap,
  Home,
  IdCard,
  Languages,
  Layers,
  Package,
  School,
  SlidersHorizontal,
  Tags,
  Users,
} from '@lucide/vue'

export type SetupTabId =
  | 'profile'
  | 'academic-setup'
  | 'grade-levels'
  | 'classes'
  | 'streams'
  | 'houses'
  | 'subjects'
  | 'departments'
  | 'subject-packages'
  | 'grading'
  | 'rooms'
  | 'fees'
  | 'student-categories'
  | 'currencies'
  | 'languages'
  | 'designations'
  | 'notifications'

export interface SetupSection {
  listKey: string
  title?: string
  description?: string
}

export interface SetupTab {
  id: SetupTabId
  title: string
  description: string
  icon: Component
  /** Single registry CRUD section. */
  listKey?: string
  /** Multiple registry CRUD sections stacked in one tab. */
  sections?: SetupSection[]
  progressKey?:
    | 'profile'
    | 'terms'
    | 'gradeLevels'
    | 'classes'
    | 'streams'
    | 'subjects'
    | 'rooms'
    | 'fees'
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
    title: 'Terms',
    description:
      'Create academic terms and mark the current term used by gradebook, exams, attendance, and reports.',
    icon: CalendarDays,
    listKey: 'academics-terms',
    progressKey: 'terms',
  },
  {
    id: 'grade-levels',
    title: 'Grade Levels',
    description:
      'Define year levels such as Grade 1–7, Form 1–4, Lower 6, and Upper 6. Classes and subject packages link to these levels.',
    icon: Layers,
    listKey: 'academics-grade-levels',
    progressKey: 'gradeLevels',
  },
  {
    id: 'classes',
    title: 'Classes',
    description:
      'Create class groups from a grade level and optional stream — e.g. Form 4 + A2 → Form 4A2, or Lower 6 + Commercials → Lower 6 Commercials.',
    icon: GraduationCap,
    listKey: 'academics-setup',
    progressKey: 'classes',
  },
  {
    id: 'streams',
    title: 'Streams',
    description:
      'Form 1–4 section codes (A, B, A2) or A-level pathways (Commercials, Sciences, Arts) used when naming classes.',
    icon: GitBranch,
    listKey: 'academics-streams',
    progressKey: 'streams',
  },
  {
    id: 'houses',
    title: 'Houses',
    description:
      'Pastoral houses used for student grouping, competitions, and school culture.',
    icon: Home,
    listKey: 'academics-houses',
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
    id: 'departments',
    title: 'Departments',
    description:
      'Academic departments that group subjects and teaching staff.',
    icon: Building2,
    listKey: 'academics-departments',
  },
  {
    id: 'subject-packages',
    title: 'Subject Packages',
    description:
      'Assign which subjects are offered for each grade level and optional stream.',
    icon: Package,
    listKey: 'academics-subject-packages',
  },
  {
    id: 'grading',
    title: 'Grading Scales',
    description:
      'Mark bands and grade letters used when converting scores in the gradebook and exam results.',
    icon: SlidersHorizontal,
    listKey: 'academics-grading-scales',
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
    id: 'student-categories',
    title: 'Student Categories',
    description:
      'Groupings used for fee discounts and reporting (e.g. boarder, day scholar).',
    icon: Users,
    listKey: 'people-student-categories',
  },
  {
    id: 'designations',
    title: 'Designations',
    description:
      'Job titles for teaching and non-teaching staff used in HR and payroll.',
    icon: IdCard,
    listKey: 'hr-designations',
  },
  {
    id: 'notifications',
    title: 'Notification',
    description:
      'Store school preferences for outbound notice channels. Delivery wiring uses these flags as they are enabled.',
    icon: Bell,
  },
  {
    id: 'currencies',
    title: 'Currencies',
    description:
      'School currencies for fees, payroll, and multi-currency reporting.',
    icon: Coins,
    listKey: 'settings-currencies',
  },
  {
    id: 'languages',
    title: 'Languages',
    description:
      'Preferred languages for school communications and UI preference.',
    icon: Languages,
    listKey: 'settings-languages',
  },
  {
    id: 'fees',
    title: 'Fees',
    description:
      'Define fee categories, groups, and discounts, then set amounts by class so invoices stay consistent.',
    icon: Tags,
    sections: [
      {
        listKey: 'finance-fee-categories',
        title: 'Fee categories',
        description: 'Tuition, levies, and other charge types used when building fee structures.',
      },
      {
        listKey: 'finance-fee-groups',
        title: 'Fee groups',
        description: 'Bundle fee categories for packaging on invoices.',
      },
      {
        listKey: 'finance-fee-discounts',
        title: 'Fee discounts',
        description: 'Percent or fixed discounts by fee and student category.',
      },
      {
        listKey: 'finance-fees',
        title: 'Fee structures',
        description: 'Amounts charged per class for each fee category.',
      },
    ],
    progressKey: 'fees',
  },
]

export function tabById(id: SetupTabId): SetupTab {
  return SETUP_TABS.find((tab) => tab.id === id) ?? SETUP_TABS[0]
}

export function sectionsForTab(tab: SetupTab): SetupSection[] {
  if (tab.sections?.length) return tab.sections
  if (tab.listKey) return [{ listKey: tab.listKey }]
  return []
}
