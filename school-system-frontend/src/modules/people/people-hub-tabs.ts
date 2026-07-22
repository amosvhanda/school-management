import { ClipboardList, GraduationCap, UserCheck, Users } from '@lucide/vue'
import type { ModuleHubTab } from '@/lib/module-hub'

export const PEOPLE_HUB_TABS: ModuleHubTab[] = [
  {
    id: 'students',
    title: 'Students',
    description: 'Browse and manage learner records.',
    icon: GraduationCap,
    capability: 'canManageStudents',
    listKey: 'students',
  },
  {
    id: 'teachers',
    title: 'Teachers',
    description: 'Staff records and teaching assignments.',
    icon: Users,
    capability: 'canManageTeachers',
    listKey: 'teachers',
  },
  {
    id: 'guardians',
    title: 'Guardians',
    description: 'Parents and emergency contacts.',
    icon: UserCheck,
    capability: 'canManageStudents',
    listKey: 'guardians',
  },
  {
    id: 'enrollment',
    title: 'Enrollment',
    description: 'Applications and intake pipeline.',
    icon: ClipboardList,
    capability: 'canManageStudents',
    component: () => import('@/modules/enrollment/views/EnrollmentView.vue'),
  },
]

export const PEOPLE_HUB_DEFAULT_TAB = 'students'
