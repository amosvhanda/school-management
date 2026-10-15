import { CreditCard, KeyRound, UserCog } from '@lucide/vue'
import type { ModuleHubTab } from '@/lib/module-hub'

export const ADMIN_HUB_TABS: ModuleHubTab[] = [
  {
    id: 'users',
    title: 'Users',
    description: 'Staff accounts and access.',
    icon: UserCog,
    component: () => import('@/modules/admin/views/UsersView.vue'),
  },
  {
    id: 'roles',
    title: 'Roles',
    description: 'Permissions and role assignments.',
    icon: KeyRound,
    component: () => import('@/modules/admin/views/RolesView.vue'),
  },
  {
    id: 'subscription',
    title: 'Subscription',
    description: 'School plan and license status.',
    icon: CreditCard,
    component: () => import('@/modules/admin/views/SubscriptionPlanView.vue'),
  },
]

export const ADMIN_HUB_DEFAULT_TAB = 'users'
