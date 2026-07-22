import { Megaphone, MessageSquare } from '@lucide/vue'
import type { ModuleHubTab } from '@/lib/module-hub'

export const COMMUNICATIONS_HUB_TABS: ModuleHubTab[] = [
  {
    id: 'announcements',
    title: 'Announcements',
    description: 'School-wide notices for staff, parents, and students.',
    icon: Megaphone,
    component: () => import('@/modules/communications/views/AnnouncementsView.vue'),
  },
  {
    id: 'messages',
    title: 'Messages',
    description: 'Staff and parent conversation threads.',
    icon: MessageSquare,
    component: () => import('@/modules/communications/views/CommunicationThreadsView.vue'),
  },
]

export const COMMUNICATIONS_HUB_DEFAULT_TAB = 'announcements'
