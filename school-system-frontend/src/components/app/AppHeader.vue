<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Search } from '@lucide/vue'
import { SidebarTrigger } from '@/components/ui/sidebar'
import { Separator } from '@/components/ui/separator'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import NotificationsPanel from '@/components/app/NotificationsPanel.vue'
import ThemeToggle from '@/components/app/ThemeToggle.vue'
import { useAuth } from '@/composables/useAuth'
import { useBreadcrumbs } from '@/composables/useBreadcrumbs'

const router = useRouter()
const { displayName, user, logout } = useAuth()
const { items } = useBreadcrumbs()

const pageTitle = computed(() => items.value.at(-1)?.title ?? 'Dashboard')

async function handleLogout() {
  await logout()
  await router.push({ name: 'login' })
}

const initials = computed(() =>
  displayName.value
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase(),
)
</script>

<template>
  <header
    role="banner"
    class="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-2 border-b border-border/60 bg-background/85 px-3 backdrop-blur-xl supports-[backdrop-filter]:bg-background/75 sm:gap-3 sm:px-4"
  >
    <div class="flex min-w-0 flex-1 items-center gap-2">
      <SidebarTrigger class="size-9 shrink-0" aria-label="Toggle navigation sidebar" />
      <Separator orientation="vertical" class="hidden h-4 sm:block" aria-hidden="true" />

      <Breadcrumb class="hidden min-w-0 lg:flex" aria-label="Breadcrumb">
        <BreadcrumbList>
          <BreadcrumbItem v-for="(item, index) in items" :key="item.href">
            <BreadcrumbLink v-if="!item.isLast" as-child>
              <RouterLink :to="item.href">{{ item.title }}</RouterLink>
            </BreadcrumbLink>
            <template v-else>
              <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
            </template>
            <BreadcrumbSeparator v-if="index < items.length - 1" />
          </BreadcrumbItem>
        </BreadcrumbList>
      </Breadcrumb>

      <p class="min-w-0 truncate text-sm font-medium lg:hidden" :title="pageTitle">
        {{ pageTitle }}
      </p>
    </div>

    <div class="relative hidden w-full max-w-xs items-center md:flex lg:max-w-sm xl:max-w-md">
      <Search class="pointer-events-none absolute left-2.5 size-4 text-muted-foreground" aria-hidden="true" />
      <Input
        type="search"
        placeholder="Search modules…"
        class="h-9 w-full border-border/70 bg-muted/40 pl-9 text-sm shadow-none"
        aria-label="Search modules"
        disabled
      />
    </div>

    <div class="flex shrink-0 items-center gap-0.5 sm:gap-1">
      <Button
        variant="ghost"
        size="icon"
        class="size-9 md:hidden"
        aria-label="Search modules"
        disabled
      >
        <Search class="size-4" aria-hidden="true" />
      </Button>

      <NotificationsPanel />
      <ThemeToggle />

      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="ghost"
            class="relative ml-0.5 h-9 gap-2 rounded-full px-2 sm:ml-1"
            :aria-label="`Account menu for ${displayName}`"
          >
            <Avatar class="size-7">
              <AvatarFallback class="text-xs font-semibold">{{ initials }}</AvatarFallback>
            </Avatar>
            <span class="hidden max-w-[120px] truncate text-sm font-medium xl:inline text-foreground">
              {{ displayName }}
            </span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
          <DropdownMenuLabel>
            <div class="flex flex-col gap-0.5">
              <span class="text-sm font-semibold text-foreground">{{ displayName }}</span>
              <span class="text-xs font-normal capitalize text-muted-foreground">{{ user?.role }}</span>
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem class="text-sm cursor-pointer" @click="router.push('/settings')">Settings</DropdownMenuItem>
          <DropdownMenuItem class="text-sm cursor-pointer text-destructive focus:text-destructive" @click="handleLogout">Sign out</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </header>
</template>
