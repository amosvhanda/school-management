import { Bell, Bus, FileCheck, Megaphone, MessageSquare, ShoppingBag } from '@lucide/vue'
import type { ModuleHubTab } from '@/lib/module-hub'

export const PARENT_HUB_TABS: ModuleHubTab[] = [
  {
    id: 'messages',
    title: 'Messages',
    description: 'Contact the school about your children.',
    icon: MessageSquare,
    component: () => import('@/modules/parent-portal/views/ParentMessagesView.vue'),
  },
  {
    id: 'announcements',
    title: 'Announcements',
    description: 'School notices for parents.',
    icon: Megaphone,
    listKey: 'portal-announcements',
  },
  {
    id: 'notifications',
    title: 'Notifications',
    description: 'Alerts for your linked children.',
    icon: Bell,
    component: () => import('@/modules/parent-portal/views/ParentNotificationsView.vue'),
  },
  {
    id: 'consent',
    title: 'Consent',
    description: 'Approve or decline school consent forms.',
    icon: FileCheck,
    component: () => import('@/modules/parent-portal/views/ParentConsentView.vue'),
  },
  {
    id: 'store',
    title: 'Store',
    description: 'Order uniforms and school stock.',
    icon: ShoppingBag,
    component: () => import('@/modules/parent-portal/views/ParentStoreView.vue'),
  },
  {
    id: 'trips',
    title: 'Trips',
    description: 'Register your child for upcoming trips.',
    icon: Bus,
    component: () => import('@/modules/parent-portal/views/ParentTripsView.vue'),
  },
]

export const PARENT_HUB_DEFAULT_TAB = 'messages'
