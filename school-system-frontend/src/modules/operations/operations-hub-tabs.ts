import {
  Building2,
  Bus,
  CalendarHeart,
  HeartPulse,
  Library,
  Package,
  ShoppingBag,
  UserCheck,
} from '@lucide/vue'
import type { ModuleHubTab } from '@/lib/module-hub'

export const OPERATIONS_HUB_TABS: ModuleHubTab[] = [
  {
    id: 'inventory',
    title: 'Inventory',
    description: 'Stock items and till sales.',
    icon: Package,
    capability: 'canManageInventory',
    sections: [
      {
        listKey: 'ops-inventory',
        title: 'Stock',
        description: 'Uniforms, stationery, and school store stock.',
      },
      {
        listKey: 'ops-inventory-sales',
        title: 'Sales',
        description: 'Record sales against student accounts.',
      },
    ],
  },
  {
    id: 'library-books',
    title: 'Library Books',
    description: 'Catalogue and copy records.',
    icon: Library,
    capability: 'canManageLibrary',
    listKey: 'ops-library',
  },
  {
    id: 'library-members',
    title: 'Library Members',
    description: 'Registered borrowers — students, staff, and external.',
    icon: Library,
    capability: 'canManageLibrary',
    listKey: 'ops-library-members',
  },
  {
    id: 'library-loans',
    title: 'Issue / Return',
    description: 'Issue books to members and record returns.',
    icon: Library,
    capability: 'canManageLibrary',
    listKey: 'ops-library-loans',
  },
  {
    id: 'transport',
    title: 'Transport',
    description: 'Vehicles, drivers, and bus routes.',
    icon: Bus,
    capability: 'canManageTransport',
    sections: [
      {
        listKey: 'ops-transport',
        title: 'Vehicles',
        description: 'School buses and fleet records.',
      },
      {
        listKey: 'ops-transport-drivers',
        title: 'Drivers',
        description: 'Driver profiles and licence details.',
      },
      {
        listKey: 'ops-transport-routes',
        title: 'Routes',
        description: 'Pickup and drop-off routes.',
      },
    ],
  },
  {
    id: 'visitors',
    title: 'Visitors',
    description: 'Front desk visitor check-in.',
    icon: UserCheck,
    capability: 'canManageReception',
    listKey: 'ops-visitors',
  },
  {
    id: 'events',
    title: 'Events',
    description: 'School events and calendar bookings.',
    icon: CalendarHeart,
    capability: 'canManageTeachers',
    listKey: 'ops-events',
  },
  {
    id: 'trips',
    title: 'Trips',
    description: 'School trips and parent registration fees.',
    icon: ShoppingBag,
    capability: 'canManageTeachers',
    listKey: 'ops-school-trips',
  },
  {
    id: 'hostels',
    title: 'Hostels',
    description: 'Boarding houses and room assignments.',
    icon: Building2,
    capability: 'canManageTeachers',
    listKey: 'ops-hostels',
  },
  {
    id: 'health',
    title: 'Clinic',
    description: 'Clinic visits and health notes.',
    icon: HeartPulse,
    capability: 'canManageTeachers',
    listKey: 'ops-health',
  },
]

export const OPERATIONS_HUB_DEFAULT_TAB = 'inventory'
