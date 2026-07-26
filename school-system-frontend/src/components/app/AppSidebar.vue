<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import * as icons from '@lucide/vue'
import { ChevronRight, GraduationCap } from '@lucide/vue'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
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
import { usePermissions } from '@/composables/usePermissions'
import { useAuth } from '@/composables/useAuth'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
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
const { filterNavigation } = usePermissions()
const { user, defaultRoute } = useAuth()
const { school } = useSchoolProfile()
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
  return isNavHrefActive(href, route.path, route.query.tab, siblingHrefs(siblings))
}

function hasActiveDescendant(item: NavItem): boolean {
  const current = currentNavTarget(route.path, route.query.tab)
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
    <SidebarHeader class="border-b border-sidebar-border/80 px-2 py-3 md:px-3 md:py-4">
      <RouterLink
        :to="homeHref"
        class="flex items-center gap-3 rounded-xl px-2 py-1.5 transition-colors hover:bg-sidebar-accent group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
        :aria-label="`${school?.name ?? title} home`"
      >
        <div
          class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground"
        >
          <GraduationCap class="size-4" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1 group-data-[collapsible=icon]:hidden">
          <p class="truncate text-sm font-semibold leading-none tracking-tight text-sidebar-foreground">{{ school?.name ?? title }}</p>
          <p class="mt-1.5 truncate text-[11px] text-sidebar-foreground/55">
            {{ school?.code ? `${school.code} · ` : '' }}{{ title }}
          </p>
        </div>
      </RouterLink>
    </SidebarHeader>

    <SidebarContent class="gap-0 px-1 py-2 md:px-2" aria-label="Main menu">
      <SidebarGroup v-for="group in groups" :key="group.label" class="p-0">
        <SidebarGroupContent>
          <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in group.items" :key="item.title" class="relative">
              <!-- Expandable EduDash-style module -->
              <template v-if="item.items?.length">
                <SidebarMenuButton
                  :tooltip="item.title"
                  :is-active="hasActiveDescendant(item)"
                  class="h-9 rounded-lg px-2.5 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-9 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0 data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-primary"
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
                  class="h-9 rounded-lg px-2.5 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-9 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0 data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-primary"
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

    <SidebarFooter class="border-t border-sidebar-border/80 p-2 md:p-3">
      <div class="rounded-xl border border-sidebar-border/60 bg-sidebar-accent/40 px-3 py-2.5 group-data-[collapsible=icon]:hidden">
        <p class="truncate text-xs font-semibold text-sidebar-foreground">{{ user?.name ?? 'User' }}</p>
        <p class="mt-0.5 truncate text-[11px] capitalize text-sidebar-foreground/55">
          {{ user?.role?.replaceAll('_', ' ') ?? title }}
        </p>
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
