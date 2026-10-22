<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import * as icons from '@lucide/vue'
import {
  Check,
  ChevronsUpDown,
  GraduationCap,
  LogOut,
  Menu,
  Minus,
  PanelLeft,
  Plus,
  UserRound,
} from '@lucide/vue'
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { usePermissions } from '@/composables/usePermissions'
import { useAuth } from '@/composables/useAuth'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { useNotificationStore } from '@/stores/notification.store'
import type { NavGroup, NavItem } from '@/types/navigation'
import { cn } from '@/lib/utils'
import {
  flattenNavLinks,
  isNavHrefActive,
} from '@/lib/nav-href'

const props = defineProps<{ navigation: NavGroup[]; title: string }>()

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { filterNavigation, isSuperAdmin } = usePermissions()
const { user, defaultRoute, switchSchool, loading, logout, displayName } = useAuth()
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

async function handleLogout() {
  await logout()
  await router.push({ name: 'login' })
}

const notificationStore = useNotificationStore()
const { isMobile, setOpenMobile, state, toggleSidebar } = useSidebar()

const groups = computed(() => filterNavigation(props.navigation))
const isCollapsed = computed(() => state.value === 'collapsed')
const homeHref = computed(() => defaultRoute.value || '/')
/** Accordion: at most one manually opened submenu (active branch always stays open). */
const openMenuKey = ref<string | null>(null)

const userInitials = computed(() =>
  (displayName.value || user.value?.name || 'U')
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase(),
)

const roleLabel = computed(() =>
  (user.value?.role ?? 'user').replaceAll('_', ' '),
)

function resolveIcon(name?: string) {
  if (!name) return icons.LayoutDashboard
  return ((icons as Record<string, unknown>)[name] as typeof icons.LayoutDashboard) ?? icons.Circle
}

const flatLinks = computed(() => flattenNavLinks(groups.value))
const competitorHrefs = computed(() => flatLinks.value.map((link) => link.href))
const competitorKeys = computed(() => flatLinks.value.map((link) => link.key))

function linkKey(groupLabel: string, item: NavItem, child?: NavItem) {
  if (child) return `${groupLabel}::${item.title}::${child.title}`
  return `${groupLabel}::${item.title}`
}

function isItemActive(groupLabel: string, item: NavItem, child?: NavItem) {
  const href = child?.href ?? item.href
  return isNavHrefActive(
    href,
    route.path,
    route.query.tab,
    competitorHrefs.value,
    route.query.status,
    route.query.staff,
    linkKey(groupLabel, item, child),
    competitorKeys.value,
  )
}

function hasActiveDescendant(groupLabel: string, item: NavItem): boolean {
  if (item.href && isItemActive(groupLabel, item)) return true
  return (item.items ?? []).some((child) => isItemActive(groupLabel, item, child))
}

function menuKey(groupLabel: string, item: NavItem) {
  return `${groupLabel}::${item.title}`
}

function isMenuOpen(groupLabel: string, item: NavItem) {
  if (hasActiveDescendant(groupLabel, item)) return true
  return openMenuKey.value === menuKey(groupLabel, item)
}

function toggleMenu(groupLabel: string, item: NavItem) {
  const key = menuKey(groupLabel, item)
  openMenuKey.value = openMenuKey.value === key ? null : key
}

/** Expand/collapse submenu; when icon-collapsed, navigate to primary child instead. */
function onExpandableClick(groupLabel: string, item: NavItem) {
  if (isCollapsed.value && primaryChildHref(item)) {
    return
  }
  toggleMenu(groupLabel, item)
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

/** Leaf active = full accent; parent open/section = soft highlight without the active rail. */
function rowActiveClass(active: boolean, open = false) {
  return cn(
    'relative h-11 rounded-xl px-2.5 text-[15px] font-medium transition-colors duration-150 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-10 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:rounded-xl group-data-[collapsible=icon]:px-0',
    active
      ? 'bg-accent text-primary shadow-none hover:bg-accent before:absolute before:inset-y-2 before:left-0 before:w-[3px] before:rounded-full before:bg-primary group-data-[collapsible=icon]:before:hidden'
      : open
        ? 'bg-muted/45 text-sidebar-foreground hover:bg-muted/60'
        : 'bg-transparent text-sidebar-foreground/85 hover:bg-muted/50 hover:text-sidebar-foreground',
  )
}

/** PreSkool: circular tint when open/active; plain icon when idle. */
function iconBoxClass(active: boolean) {
  return cn(
    'flex size-7 shrink-0 items-center justify-center rounded-full transition-colors duration-150 [&_svg]:size-[18px]!',
    active ? 'bg-primary/15 text-primary' : 'bg-transparent text-muted-foreground',
  )
}

watch(
  () => [route.path, route.query.tab] as const,
  () => {
    if (isMobile.value) {
      setOpenMobile(false)
    }
    for (const group of groups.value) {
      for (const item of group.items) {
        if (item.items?.length && hasActiveDescendant(group.label, item)) {
          openMenuKey.value = menuKey(group.label, item)
        }
      }
    }
  },
  { immediate: true },
)
</script>

<template>
  <Sidebar collapsible="icon" class="border-r border-sidebar-border bg-sidebar dark:border-sidebar-border/80" aria-label="Application navigation">
    <SidebarHeader class="gap-2 border-b border-sidebar-border/70 px-3 group-data-[collapsible=icon]:px-2">
      <div class="flex h-16 items-center gap-2 group-data-[collapsible=icon]:justify-center">
        <!-- Collapsed: logo expands the sidebar (hamburger was hidden in icon mode). -->
        <button
          v-if="isCollapsed"
          type="button"
          class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring"
          aria-label="Expand navigation sidebar"
          @click="toggleSidebar"
        >
          <PanelLeft class="size-4" aria-hidden="true" />
        </button>
        <template v-else>
          <RouterLink
            :to="homeHref"
            class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl px-1 py-1"
            :aria-label="`${title} home`"
          >
            <div
              class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground"
            >
              <GraduationCap class="size-4" aria-hidden="true" />
            </div>
            <span class="truncate text-lg font-semibold tracking-tight text-sidebar-foreground">
              {{ title }}
            </span>
          </RouterLink>
          <button
            type="button"
            class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-muted-foreground outline-none hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"
            aria-label="Collapse navigation sidebar"
            @click="toggleSidebar"
          >
            <Menu class="size-5" aria-hidden="true" />
          </button>
        </template>
      </div>

      <DropdownMenu v-if="isSuperAdmin">
        <DropdownMenuTrigger as-child>
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl bg-muted/50 px-3 py-2 text-left ring-1 ring-border/70 outline-none group-data-[collapsible=icon]:hidden focus-visible:ring-2 focus-visible:ring-ring"
            :disabled="switchingSchool || loading"
            :aria-busy="switchingSchool"
            aria-label="Switch school"
          >
            <div
              class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
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
    </SidebarHeader>

    <SidebarContent class="gap-0 px-3 py-3" aria-label="Main menu">
      <SidebarGroup v-for="(group, groupIndex) in groups" :key="group.label || `group-${groupIndex}`" class="p-0 py-2">
        <SidebarGroupLabel
          v-if="group.label"
          class="mb-1.5 h-auto px-2.5 py-0 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground/80"
        >
          {{ group.label }}
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in group.items" :key="item.title" class="relative">
              <!-- Expandable ProSkool module -->
              <template v-if="item.items?.length">
                <SidebarMenuButton
                  :tooltip="item.title"
                  :is-active="false"
                  :class="rowActiveClass(
                    false,
                    isMenuOpen(group.label, item) && !isCollapsed,
                  )"
                  :aria-expanded="isMenuOpen(group.label, item)"
                  @click="onExpandableClick(group.label, item)"
                >
                  <RouterLink
                    v-if="isCollapsed && primaryChildHref(item)"
                    :to="primaryChildHref(item)!"
                    class="flex w-full items-center justify-center"
                    :title="item.title"
                    @click.stop
                  >
                    <span :class="iconBoxClass(hasActiveDescendant(group.label, item))">
                      <component :is="resolveIcon(item.icon)" aria-hidden="true" />
                    </span>
                    <span class="sr-only">{{ item.title }}</span>
                  </RouterLink>
                  <template v-else>
                    <span :class="iconBoxClass(hasActiveDescendant(group.label, item) || isMenuOpen(group.label, item))">
                      <component :is="resolveIcon(item.icon)" aria-hidden="true" />
                    </span>
                    <span class="min-w-0 flex-1 truncate text-left text-[15px] font-medium leading-snug">
                      {{ item.title }}
                    </span>
                    <span
                      class="flex size-5 shrink-0 items-center justify-center text-muted-foreground/70 group-data-[collapsible=icon]:hidden"
                      :class="isMenuOpen(group.label, item) ? 'text-primary' : ''"
                      aria-hidden="true"
                    >
                      <Minus v-if="isMenuOpen(group.label, item)" class="size-3.5" />
                      <Plus v-else class="size-3.5" />
                    </span>
                  </template>
                </SidebarMenuButton>

                <SidebarMenuSub
                  v-show="isMenuOpen(group.label, item) && !isCollapsed"
                  class="mx-0 mb-1 mt-0.5 border-l-0 px-0 py-0"
                >
                  <SidebarMenuSubItem v-for="child in item.items" :key="child.title">
                    <SidebarMenuSubButton
                      as-child
                      size="sm"
                      :is-active="isItemActive(group.label, item, child)"
                      class="h-9 rounded-lg pl-9 text-sm font-normal text-sidebar-foreground/75 hover:bg-muted/50 hover:text-sidebar-foreground focus-visible:ring-1 focus-visible:ring-ring/40 data-[active=true]:bg-primary/10 data-[active=true]:font-medium data-[active=true]:text-primary data-[active=true]:shadow-none"
                    >
                      <RouterLink
                        :to="child.href ?? '#'"
                        :aria-current="isItemActive(group.label, item, child) ? 'page' : undefined"
                        @click="openMenu(group.label, item)"
                      >
                        <span class="truncate">{{ child.title }}</span>
                      </RouterLink>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </template>

              <!-- Flat leaf (Dashboard / Application / Reports) -->
              <template v-else>
                <SidebarMenuButton
                  as-child
                  :tooltip="item.title"
                  :is-active="isItemActive(group.label, item)"
                  :class="rowActiveClass(isItemActive(group.label, item))"
                >
                  <RouterLink
                    :to="item.href ?? '#'"
                    class="gap-2.5 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:gap-0"
                    :aria-current="isItemActive(group.label, item) ? 'page' : undefined"
                    :title="isCollapsed ? item.title : undefined"
                  >
                    <span :class="iconBoxClass(isItemActive(group.label, item))">
                      <component :is="resolveIcon(item.icon)" aria-hidden="true" />
                    </span>
                    <span class="truncate text-[15px] font-medium leading-snug group-data-[collapsible=icon]:sr-only">
                      {{ item.title }}
                    </span>
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
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl bg-muted/35 px-2.5 py-2 text-left outline-none transition-colors hover:bg-muted/65 focus-visible:ring-2 focus-visible:ring-ring group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
            aria-label="Account menu"
          >
            <Avatar class="size-9 shrink-0">
              <AvatarImage v-if="user?.avatar_url" :src="user.avatar_url" :alt="displayName || 'User'" />
              <AvatarFallback class="bg-primary/10 text-xs font-semibold text-primary">
                {{ userInitials }}
              </AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1 group-data-[collapsible=icon]:hidden">
              <p class="truncate text-base font-medium text-sidebar-foreground">
                {{ displayName || user?.name || 'User' }}
              </p>
              <p class="mt-0.5 truncate text-sm capitalize text-muted-foreground">
                {{ roleLabel }}
              </p>
            </div>
            <ChevronsUpDown
              class="size-4 shrink-0 text-muted-foreground group-data-[collapsible=icon]:hidden"
              aria-hidden="true"
            />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent side="top" align="start" class="w-56 rounded-xl p-1.5">
          <DropdownMenuItem class="cursor-pointer gap-2 rounded-lg" as-child>
            <RouterLink to="/profile">
              <UserRound class="size-4" aria-hidden="true" />
              Profile
            </RouterLink>
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem class="cursor-pointer gap-2 rounded-lg text-destructive focus:text-destructive" @click="handleLogout">
            <LogOut class="size-4" aria-hidden="true" />
            Log out
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarFooter>
    <SidebarRail aria-hidden="true" />
  </Sidebar>
</template>
