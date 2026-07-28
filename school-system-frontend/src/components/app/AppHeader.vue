<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Building2, ChevronsUpDown } from '@lucide/vue'
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import NotificationsPanel from '@/components/app/NotificationsPanel.vue'
import ThemeToggle from '@/components/app/ThemeToggle.vue'
import { useAuth } from '@/composables/useAuth'
import { useBreadcrumbs } from '@/composables/useBreadcrumbs'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'

const router = useRouter()
const toast = useToast()
const { displayName, user, logout, canAccess, checkCapability, switchSchool, loading } = useAuth()
const { items } = useBreadcrumbs()
const switchingSchool = ref(false)

const canViewProfile = computed(() =>
  canAccess('isStaff') || canAccess('isParent') || user.value?.role === 'student',
)
const canManageSchoolSettings = computed(() => checkCapability('canManageTeachers'))

const pageTitle = computed(() => items.value.at(-1)?.title ?? 'Dashboard')

const availableSchools = computed(() => user.value?.schools ?? [])
const canSwitchSchool = computed(() => availableSchools.value.length > 1)
const currentSchool = computed(() =>
  availableSchools.value.find((s) => s.is_current || s.id === user.value?.school_id)
  ?? availableSchools.value[0]
  ?? null,
)

async function handleLogout() {
  await logout()
  await router.push({ name: 'login' })
}

async function handleSwitchSchool(schoolId: number) {
  if (!schoolId || schoolId === user.value?.school_id || switchingSchool.value) return
  const target = availableSchools.value.find((s) => s.id === schoolId)
  switchingSchool.value = true
  try {
    await switchSchool(schoolId)
    toast.success(
      'School switched',
      `You are now working in ${target?.name ?? 'the selected school'}.`,
    )
    window.location.reload()
  } catch (err) {
    toast.error('Could not switch school', getErrorMessage(err))
  } finally {
    switchingSchool.value = false
  }
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

    <div class="flex shrink-0 items-center gap-0.5 sm:gap-1">
      <DropdownMenu v-if="canSwitchSchool">
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            size="sm"
            class="mr-1 hidden max-w-[11rem] gap-1.5 sm:inline-flex"
            :disabled="switchingSchool || loading"
            :aria-busy="switchingSchool"
            aria-label="Switch school"
          >
            <Building2 class="size-3.5 shrink-0" aria-hidden="true" />
            <span class="truncate">{{ currentSchool?.name ?? 'School' }}</span>
            <ChevronsUpDown class="size-3.5 shrink-0 opacity-60" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-64">
          <DropdownMenuLabel>Switch school</DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            v-for="school in availableSchools"
            :key="school.id"
            class="cursor-pointer"
            :disabled="school.id === user?.school_id || school.status !== 'active'"
            @click="handleSwitchSchool(school.id)"
          >
            <div class="flex min-w-0 flex-col">
              <span class="truncate font-medium">{{ school.name }}</span>
              <span class="text-xs text-muted-foreground">
                {{ school.code || `School #${school.id}` }}
                <template v-if="school.id === user?.school_id"> · current</template>
              </span>
            </div>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

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
              <AvatarImage v-if="user?.avatar_url" :src="user.avatar_url" :alt="displayName" />
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
              <span v-if="currentSchool" class="text-xs font-normal text-muted-foreground">
                {{ currentSchool.name }}
              </span>
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <template v-if="canSwitchSchool">
            <DropdownMenuLabel class="text-xs font-normal text-muted-foreground">Schools</DropdownMenuLabel>
            <DropdownMenuItem
              v-for="school in availableSchools"
              :key="`menu-${school.id}`"
              class="cursor-pointer sm:hidden"
              :disabled="school.id === user?.school_id || school.status !== 'active'"
              @click="handleSwitchSchool(school.id)"
            >
              {{ school.name }}
            </DropdownMenuItem>
            <DropdownMenuSeparator class="sm:hidden" />
          </template>
          <DropdownMenuItem v-if="canViewProfile" class="text-sm cursor-pointer" @click="router.push('/profile')">My profile</DropdownMenuItem>
          <DropdownMenuItem
            v-if="canManageSchoolSettings"
            class="text-sm cursor-pointer"
            @click="router.push('/settings')"
          >
            School setup
          </DropdownMenuItem>
          <DropdownMenuItem class="text-sm cursor-pointer text-destructive focus:text-destructive" @click="handleLogout">Sign out</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </header>
</template>
