import { Inbox, Megaphone, MessageSquare } from '@lucide/vue'
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
  {
    id: 'contact-messages',
    title: 'Contact Messages',
    description: 'Support inbox — phone, email, and walk-in enquiries logged as tickets.',
    icon: Inbox,
    capability: 'canManageReception',
    component: () => import('@/modules/communications/views/ContactMessagesView.vue'),
  },
]

export const COMMUNICATIONS_HUB_DEFAULT_TAB = 'announcements'
