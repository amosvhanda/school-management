<script setup lang="ts">
import { computed, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import * as icons from '@lucide/vue'
import { GraduationCap } from '@lucide/vue'
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
  SidebarRail,
  useSidebar,
} from '@/components/ui/sidebar'
import { usePermissions } from '@/composables/usePermissions'
import { useAuth } from '@/composables/useAuth'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { useNotificationStore } from '@/stores/notification.store'
import type { NavGroup } from '@/types/navigation'

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

function resolveIcon(name?: string) {
  if (!name) return icons.LayoutDashboard
  return (icons as Record<string, unknown>)[name] as typeof icons.LayoutDashboard ?? icons.Circle
}

function isActive(href?: string) {
  if (!href) return false
  if (href === '/') return route.path === '/'
  return route.path === href || route.path.startsWith(`${href}/`)
}

/** Prefer the most specific nav match so `/finance` is not active on `/finance/analytics`. */
function isNavItemActive(href: string | undefined, siblings: Array<{ href?: string }>) {
  if (!href || !isActive(href)) return false
  const longerMatch = siblings.some(
    (item) =>
      item.href &&
      item.href !== href &&
      item.href.startsWith(href) &&
      (route.path === item.href || route.path.startsWith(`${item.href}/`)),
  )
  return !longerMatch
}

function badgeCount(item: { badge?: string }) {
  if (item.badge === 'workflows') return notificationStore.workflowCount
  return 0
}

watch(
  () => route.path,
  () => {
    if (isMobile.value) {
      setOpenMobile(false)
    }
  },
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

    <SidebarContent class="gap-0 px-1 py-3 md:px-2" aria-label="Main menu">
      <SidebarGroup v-for="group in groups" :key="group.label" class="pb-1 md:pb-2">
        <SidebarGroupLabel class="px-2 text-[10px] font-semibold tracking-[0.14em] text-sidebar-foreground/45 uppercase">
          {{ group.label }}
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in group.items" :key="item.title" class="relative">
              <SidebarMenuButton
                as-child
                :tooltip="item.title"
                :is-active="isNavItemActive(item.href, group.items)"
                class="h-9 rounded-lg px-2.5 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-9 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0 data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-primary data-[active=true]:shadow-none"
              >
                <RouterLink
                  :to="item.href ?? '#'"
                  class="gap-2.5 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:gap-0"
                  :aria-current="isNavItemActive(item.href, group.items) ? 'page' : undefined"
                  :title="isCollapsed ? item.title : undefined"
                >
                  <component :is="resolveIcon(item.icon)" class="size-4 shrink-0 opacity-80" aria-hidden="true" />
                  <span class="truncate group-data-[collapsible=icon]:sr-only">{{ item.title }}</span>
                </RouterLink>
              </SidebarMenuButton>
              <SidebarMenuBadge v-if="badgeCount(item) > 0" class="bg-sidebar-primary text-sidebar-primary-foreground font-semibold text-[10px] h-4 min-w-4 px-1 rounded-full">{{ badgeCount(item) }}</SidebarMenuBadge>
              <span
                v-if="badgeCount(item) > 0 && isCollapsed"
                class="pointer-events-none absolute right-1.5 top-1.5 hidden size-2 rounded-full bg-destructive group-data-[collapsible=icon]:block"
                :aria-label="`${badgeCount(item)} pending workflows`"
              />
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
        class="hidden truncate text-center text-[10px] font-bold tracking-tight text-sidebar-foreground/70 group-data-[collapsible=icon]:block py-2"
        :title="user?.name"
      >
        {{ user?.name?.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase() ?? 'U' }}
      </p>
    </SidebarFooter>
    <SidebarRail aria-hidden="true" />
  </Sidebar>
</template>
