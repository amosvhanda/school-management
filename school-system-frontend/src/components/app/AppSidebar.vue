<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import * as icons from '@lucide/vue'
import { Check, ChevronRight, ChevronsUpDown, GraduationCap, Plus } from '@lucide/vue'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuBadge,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  SidebarRail,
  useSidebar,
} from '@/components/ui/sidebar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { usePermissions } from '@/composables/usePermissions'
import { useAuth } from '@/composables/useAuth'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { useNotificationStore } from '@/stores/notification.store'
import type { NavGroup, NavItem } from '@/types/navigation'
import { cn } from '@/lib/utils'
import {
  currentNavTarget,
  isNavHrefActive,
  navTargetsEqual,
  parseNavHref,
} from '@/lib/nav-href'

const props = defineProps<{ navigation: NavGroup[]; title: string }>()

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { filterNavigation, isSuperAdmin } = usePermissions()
const { user, defaultRoute, switchSchool, loading } = useAuth()
const { school } = useSchoolProfile()
const switchingSchool = ref(false)

const availableSchools = computed(() => user.value?.schools ?? [])
const currentSchool = computed(() =>
  availableSchools.value.find((item) => item.is_current || item.id === user.value?.school_id)
  ?? null,
)
const schoolCardName = computed(() => {
  if (currentSchool.value?.name) return currentSchool.value.name
  if (isSuperAdmin.value) return 'All schools'
  return school.value?.name ?? props.title
})

const badgeTones = [
  'bg-chart-2/15 text-chart-2',
  'bg-chart-5/15 text-chart-5',
  'bg-chart-4/15 text-chart-4',
  'bg-chart-3/20 text-chart-3',
]

function schoolInitial(name: string) {
  return name.trim().charAt(0).toUpperCase() || 'S'
}

function badgeTone(index: number) {
  return badgeTones[index % badgeTones.length]
}

async function handleSwitchSchool(schoolId: number) {
  if (!schoolId || schoolId === user.value?.school_id || switchingSchool.value) return
  const target = availableSchools.value.find((item) => item.id === schoolId)
  if (target && target.status && target.status !== 'active') return
  switchingSchool.value = true
  try {
    await switchSchool(schoolId)
    toast.success('School switched', `You are now working in ${target?.name ?? 'the selected school'}.`)
    window.location.reload()
  } catch (err) {
    toast.error('Could not switch school', getErrorMessage(err))
    switchingSchool.value = false
  }
}

function addSchool() {
  void router.push({ path: '/platform/licenses', query: { register: '1' } })
}
const notificationStore = useNotificationStore()
const { isMobile, setOpenMobile, state } = useSidebar()

const groups = computed(() => filterNavigation(props.navigation))
const isCollapsed = computed(() => state.value === 'collapsed')
const homeHref = computed(() => defaultRoute.value || '/')
/** Accordion: at most one manually opened submenu (active branch always stays open). */
const openMenuKey = ref<string | null>(null)

function resolveIcon(name?: string) {
  if (!name) return icons.LayoutDashboard
  return ((icons as Record<string, unknown>)[name] as typeof icons.LayoutDashboard) ?? icons.Circle
}

function siblingHrefs(items: NavItem[]): Array<string | undefined> {
  return items.map((item) => item.href)
}

function isItemActive(href: string | undefined, siblings: NavItem[]) {
  return isNavHrefActive(
    href,
    route.path,
    route.query.tab,
    siblingHrefs(siblings),
    route.query.status,
    route.query.staff,
  )
}

function hasActiveDescendant(item: NavItem): boolean {
  const current = currentNavTarget(route.path, route.query.tab, route.query.status, route.query.staff)
  if (item.href && navTargetsEqual(parseNavHref(item.href), current)) return true
  return (item.items ?? []).some((child) => hasActiveDescendant(child))
}

function menuKey(groupLabel: string, item: NavItem) {
  return `${groupLabel}::${item.title}`
}

function isMenuOpen(groupLabel: string, item: NavItem) {
  if (hasActiveDescendant(item)) return true
  return openMenuKey.value === menuKey(groupLabel, item)
}

function toggleMenu(groupLabel: string, item: NavItem) {
  const key = menuKey(groupLabel, item)
  openMenuKey.value = openMenuKey.value === key ? null : key
}

function openMenu(groupLabel: string, item: NavItem) {
  openMenuKey.value = menuKey(groupLabel, item)
}

function primaryChildHref(item: NavItem): string | undefined {
  return item.href ?? item.items?.find((child) => child.href)?.href
}

function badgeCount(item: { badge?: string }) {
  if (item.badge === 'workflows') return notificationStore.workflowCount
  return 0
}

watch(
  () => [route.path, route.query.tab] as const,
  () => {
    if (isMobile.value) {
      setOpenMobile(false)
    }
    for (const group of groups.value) {
      for (const item of group.items) {
        if (item.items?.length && hasActiveDescendant(item)) {
          openMenuKey.value = menuKey(group.label, item)
        }
      }
    }
  },
  { immediate: true },
)
</script>

<template>
  <Sidebar collapsible="icon" class="border-r border-sidebar-border bg-sidebar" aria-label="Application navigation">
    <SidebarHeader class="gap-3 px-3 py-4 group-data-[collapsible=icon]:px-2">
      <RouterLink
        :to="homeHref"
        class="flex items-center gap-2.5 rounded-xl px-1 py-1 group-data-[collapsible=icon]:justify-center"
        :aria-label="`${title} home`"
      >
        <div
          class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground"
        >
          <GraduationCap class="size-4" aria-hidden="true" />
        </div>
        <span class="truncate text-base font-semibold tracking-tight text-sidebar-foreground group-data-[collapsible=icon]:hidden">
          {{ title }}
        </span>
      </RouterLink>
      <DropdownMenu v-if="isSuperAdmin">
        <DropdownMenuTrigger as-child>
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-2xl bg-muted/50 px-3 py-2.5 text-left ring-1 ring-border/70 outline-none group-data-[collapsible=icon]:hidden focus-visible:ring-2 focus-visible:ring-ring"
            :disabled="switchingSchool || loading"
            :aria-busy="switchingSchool"
            aria-label="Switch school"
          >
            <div
              class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
            >
              <GraduationCap class="size-4" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold leading-tight text-sidebar-foreground">{{ schoolCardName }}</p>
              <p class="mt-0.5 truncate text-[11px] text-muted-foreground">School Management</p>
            </div>
            <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-72 rounded-2xl p-2">
          <p class="px-2 pb-2 pt-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">
            Switch school
          </p>
          <DropdownMenuItem
            v-for="(item, index) in availableSchools"
            :key="item.id"
            class="cursor-pointer gap-3 rounded-xl px-2 py-2"
            :class="item.id === user?.school_id ? 'bg-primary/10 focus:bg-primary/10' : ''"
            :disabled="switchingSchool || (item.status != null && item.status !== 'active')"
            @click="handleSwitchSchool(item.id)"
          >
            <span
              class="flex size-9 shrink-0 items-center justify-center rounded-xl text-sm font-semibold"
              :class="item.id === user?.school_id ? 'bg-primary/15 text-primary' : badgeTone(index)"
              aria-hidden="true"
            >
              {{ schoolInitial(item.name) }}
            </span>
            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm font-semibold text-sidebar-foreground">{{ item.name }}</span>
              <span class="block truncate text-xs text-muted-foreground">{{ item.subtitle || 'Primary campus' }}</span>
            </span>
            <Check
              v-if="item.id === user?.school_id"
              class="size-4 shrink-0 text-primary"
              aria-hidden="true"
            />
          </DropdownMenuItem>
          <p v-if="availableSchools.length === 0" class="px-2 py-2 text-sm text-muted-foreground">
            No schools yet.
          </p>
          <DropdownMenuSeparator class="my-2" />
          <DropdownMenuItem class="cursor-pointer gap-3 rounded-xl px-2 py-2" @click="addSchool">
            <span
              class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-dashed border-primary/40 bg-primary/5 text-primary"
              aria-hidden="true"
            >
              <Plus class="size-4" />
            </span>
            <span class="text-sm font-semibold text-primary">Add new school</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
      <RouterLink
        v-else
        :to="homeHref"
        class="flex items-center gap-3 rounded-2xl bg-muted/50 px-3 py-2.5 ring-1 ring-border/70 group-data-[collapsible=icon]:hidden"
        :aria-label="`${schoolCardName} home`"
      >
        <div
          class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
        >
          <GraduationCap class="size-4" aria-hidden="true" />
        </div>
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold leading-tight text-sidebar-foreground">{{ schoolCardName }}</p>
          <p class="mt-0.5 truncate text-[11px] text-muted-foreground">School Management</p>
        </div>
      </RouterLink>
    </SidebarHeader>

    <SidebarContent class="gap-0 px-1 py-2 md:px-2" aria-label="Main menu">
      <SidebarGroup v-for="group in groups" :key="group.label" class="p-0">
        <SidebarGroupLabel class="px-3 text-[11px] font-semibold uppercase tracking-[0.16em]">
          {{ group.label }}
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in group.items" :key="item.title" class="relative">
              <!-- Expandable EduDash-style module -->
              <template v-if="item.items?.length">
                <SidebarMenuButton
                  :tooltip="item.title"
                  :is-active="hasActiveDescendant(item)"
                  class="h-9 rounded-xl px-2.5 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-9 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0 data-[active=true]:bg-primary/10 data-[active=true]:font-medium data-[active=true]:text-primary"
                  :aria-expanded="isMenuOpen(group.label, item)"
                  @click="isCollapsed && primaryChildHref(item)
                    ? undefined
                    : toggleMenu(group.label, item)"
                >
                  <RouterLink
                    v-if="isCollapsed && primaryChildHref(item)"
                    :to="primaryChildHref(item)!"
                    class="flex w-full items-center justify-center"
                    :title="item.title"
                    @click.stop
                  >
                    <component :is="resolveIcon(item.icon)" class="size-4 shrink-0 opacity-80" aria-hidden="true" />
                    <span class="sr-only">{{ item.title }}</span>
                  </RouterLink>
                  <template v-else>
                    <component :is="resolveIcon(item.icon)" class="size-4 shrink-0 opacity-80" aria-hidden="true" />
                    <span class="truncate">{{ item.title }}</span>
                    <ChevronRight
                      class="ml-auto size-4 shrink-0 opacity-50 transition-transform group-data-[collapsible=icon]:hidden"
                      :class="cn(isMenuOpen(group.label, item) && 'rotate-90')"
                      aria-hidden="true"
                    />
                  </template>
                </SidebarMenuButton>

                <SidebarMenuSub v-show="isMenuOpen(group.label, item) && !isCollapsed">
                  <SidebarMenuSubItem v-for="child in item.items" :key="child.title">
                    <SidebarMenuSubButton
                      as-child
                      size="sm"
                      :is-active="isItemActive(child.href, item.items ?? [])"
                    >
                      <RouterLink
                        :to="child.href ?? '#'"
                        :aria-current="isItemActive(child.href, item.items ?? []) ? 'page' : undefined"
                        @click="openMenu(group.label, item)"
                      >
                        <span class="truncate">{{ child.title }}</span>
                      </RouterLink>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </template>

              <!-- Flat leaf link -->
              <template v-else>
                <SidebarMenuButton
                  as-child
                  :tooltip="item.title"
                  :is-active="isItemActive(item.href, group.items)"
                  class="h-9 rounded-xl px-2.5 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-9 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0 data-[active=true]:bg-primary/10 data-[active=true]:font-medium data-[active=true]:text-primary"
                >
                  <RouterLink
                    :to="item.href ?? '#'"
                    class="gap-2.5 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:gap-0"
                    :aria-current="isItemActive(item.href, group.items) ? 'page' : undefined"
                    :title="isCollapsed ? item.title : undefined"
                  >
                    <component :is="resolveIcon(item.icon)" class="size-4 shrink-0 opacity-80" aria-hidden="true" />
                    <span class="truncate group-data-[collapsible=icon]:sr-only">{{ item.title }}</span>
                  </RouterLink>
                </SidebarMenuButton>
                <SidebarMenuBadge
                  v-if="badgeCount(item) > 0"
                  class="bg-sidebar-primary text-sidebar-primary-foreground h-4 min-w-4 rounded-full px-1 text-[10px] font-semibold"
                >
                  {{ badgeCount(item) }}
                </SidebarMenuBadge>
              </template>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <SidebarFooter class="border-t border-sidebar-border/70 p-3">
      <div class="flex items-center gap-3 rounded-2xl px-2 py-2 group-data-[collapsible=icon]:hidden">
        <div
          class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
          aria-hidden="true"
        >
          {{ user?.name?.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase() ?? 'U' }}
        </div>
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-sidebar-foreground">{{ user?.name ?? 'User' }}</p>
          <p class="mt-0.5 truncate text-[11px] capitalize text-muted-foreground">
            {{ user?.role?.replaceAll('_', ' ') ?? title }}
          </p>
        </div>
      </div>
      <p
        class="hidden truncate py-2 text-center text-[10px] font-bold tracking-tight text-sidebar-foreground/70 group-data-[collapsible=icon]:block"
        :title="user?.name"
      >
        {{ user?.name?.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase() ?? 'U' }}
      </p>
    </SidebarFooter>
    <SidebarRail aria-hidden="true" />
  </Sidebar>
</template>
