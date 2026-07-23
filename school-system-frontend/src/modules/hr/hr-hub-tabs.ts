import { FileCheck, Palmtree, Scale, ScrollText, ShieldAlert, TriangleAlert } from '@lucide/vue'
import type { ModuleHubTab } from '@/lib/module-hub'

export const HR_HUB_TABS: ModuleHubTab[] = [
  {
    id: 'leave',
    title: 'Leave',
    description: 'Staff leave requests and approvals.',
    icon: Palmtree,
    capability: 'canManageTeachers',
    component: () => import('@/modules/hr/views/LeaveRequestsView.vue'),
  },
  {
    id: 'discipline',
    title: 'Discipline',
    description: 'Student discipline records and follow-up.',
    icon: ShieldAlert,
    capability: 'canManageStudents',
    listKey: 'hr-discipline',
  },
  {
    id: 'policies',
    title: 'Policies',
    description: 'School policies and compliance documents.',
    icon: Scale,
    capability: 'canManageTeachers',
    listKey: 'compliance',
  },
  {
    id: 'incidents',
    title: 'Incidents',
    description: 'Compliance incidents and follow-up.',
    icon: TriangleAlert,
    capability: 'canManageTeachers',
    listKey: 'compliance-incidents',
  },
  {
    id: 'consent',
    title: 'Consent',
    description: 'Consent forms for students and guardians.',
    icon: FileCheck,
    // School-wide consent admin — not class teachers.
    capability: 'canManageTeachers',
    listKey: 'compliance-consent',
  },
  {
    id: 'audit',
    title: 'Audit trail',
    description: 'Activity log and login history.',
    icon: ScrollText,
    capability: 'canViewAuditLogs',
    component: () => import('@/modules/compliance/views/AuditTrailView.vue'),
  },
]

export const HR_HUB_DEFAULT_TAB = 'leave'
