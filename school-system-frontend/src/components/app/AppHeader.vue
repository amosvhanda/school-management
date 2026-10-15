<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
  ArrowRight,
  Building2,
  CalendarDays,
  ChevronsUpDown,
  CirclePlus,
  FileBadge,
  FolderOpen,
  GraduationCap,
  LayoutGrid,
  ListTodo,
  Mail,
  MessageSquare,
  NotebookPen,
  Phone,
  Receipt,
  Search,
  Settings,
  Ticket,
  UserRound,
  Users,
  Wallet,
  Zap,
} from '@lucide/vue'
import type { Component } from 'vue'
import { SidebarTrigger } from '@/components/ui/sidebar'
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
import { Input } from '@/components/ui/input'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter'
import NotificationsPanel from '@/components/app/NotificationsPanel.vue'
import ThemeToggle from '@/components/app/ThemeToggle.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { accentSolidAt } from '@/lib/design-system'
import { useNotificationStore } from '@/stores/notification.store'
import { cn } from '@/lib/utils'

const router = useRouter()
const toast = useToast()
const notificationStore = useNotificationStore()
const { displayName, user, logout, canAccess, checkCapability, switchSchool, loading } = useAuth()
useSchoolProfile()
const { academicYear, options: academicYearOptions, setAcademicYear } = useAcademicYearFilter()
const switchingSchool = ref(false)
const searchQuery = ref('')
const searchInput = ref<HTMLInputElement | null>(null)

const canViewProfile = computed(() =>
  canAccess('isStaff') || canAccess('isParent') || user.value?.role === 'student',
)
const canManageSchoolSettings = computed(() => checkCapability('canManageTeachers'))
const canOpenMessages = computed(() => canAccess('isStaff') || canAccess('isParent'))

const availableSchools = computed(() => user.value?.schools ?? [])
const canSwitchSchool = computed(
  () => user.value?.role !== 'super_admin' && availableSchools.value.length > 1,
)
const currentSchool = computed(() =>
  availableSchools.value.find((s) => s.is_current || s.id === user.value?.school_id)
  ?? availableSchools.value[0]
  ?? null,
)

const messageBadge = computed(() => {
  if (user.value?.role === 'parent') return notificationStore.parentUnreadCount
  return 0
})

const isMac = computed(() =>
  typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform),
)

type AppTile = {
  title: string
  href: string
  icon: Component
  tone: string
  show: boolean
}

/** Workspace launcher tiles — accent colours from design-system chart tokens. */
const appLinks = computed(() => {
  const links: AppTile[] = [
    {
      title: 'Calendar',
      href: '/operations/events',
      icon: CalendarDays,
      tone: accentSolidAt(0),
      show: checkCapability('canManageTeachers') || canAccess('isStaff'),
    },
    {
      title: 'Email',
      href: '/communications/announcements',
      icon: Mail,
      tone: accentSolidAt(1),
      show: canAccess('isStaff'),
    },
    {
      title: 'Chat',
      href: '/communications/threads',
      icon: MessageSquare,
      tone: accentSolidAt(2),
      show: canOpenMessages.value,
    },
    {
      title: 'To Do',
      href: '/hr/staff-tasks',
      icon: ListTodo,
      tone: accentSolidAt(3),
      show: checkCapability('canManageTeachers') || canAccess('isStaff'),
    },
    {
      title: 'Files',
      href: '/operations/library/books',
      icon: FolderOpen,
      tone: accentSolidAt(4),
      show: checkCapability('canManageLibrary') || checkCapability('canManageTeachers'),
    },
    {
      title: 'Notes',
      href: '/reports',
      icon: NotebookPen,
      tone: accentSolidAt(5),
      show: checkCapability('canManageTeachers') || canAccess('isStaff'),
    },
    {
      title: 'Call',
      href: '/operations/front-office',
      icon: Phone,
      tone: accentSolidAt(0),
      show: checkCapability('canManageReception') || checkCapability('canManageTeachers'),
    },
    {
      title: 'Invoices',
      href: '/finance/invoices',
      icon: Receipt,
      tone: accentSolidAt(1),
      show: checkCapability('canManageFinance'),
    },
    {
      title: 'Tickets',
      href: '/workflows',
      icon: Ticket,
      tone: accentSolidAt(2),
      show: checkCapability('canManageTeachers'),
    },
  ]
  return links.filter((link) => link.show)
})

const academicYearLabel = computed(() => academicYear.value || null)

type QuickAddAction = {
  title: string
  description: string
  href: string
  icon: Component
  tone: string
  show: boolean
}

/** “Add New” shortcuts — same accent palette as launcher tiles. */
const quickAddActions = computed(() => {
  const actions: QuickAddAction[] = [
    {
      title: 'New Student',
      description: 'Admit & assign to a class',
      href: '/people?tab=students&create=1',
      icon: GraduationCap,
      tone: accentSolidAt(0),
      show: checkCapability('canManageStudents'),
    },
    {
      title: 'New Teacher',
      description: 'Add faculty & subjects',
      href: '/teachers?create=1',
      icon: UserRound,
      tone: accentSolidAt(1),
      show: checkCapability('canManageTeachers'),
    },
    {
      title: 'New Staff',
      description: 'Onboard a staff member',
      href: '/hr?tab=employees&create=1',
      icon: Users,
      tone: accentSolidAt(3),
      show: checkCapability('canManageTeachers'),
    },
    {
      title: 'Create Exam',
      description: 'Schedule a new examination',
      href: '/academics/exams?create=1',
      icon: FileBadge,
      tone: accentSolidAt(5),
      show: checkCapability('canManageExaminations') || checkCapability('canEnterExamResults'),
    },
    {
      title: 'Collect Fee',
      description: 'Record a fee payment',
      href: '/finance?tab=payments&create=1',
      icon: Wallet,
      tone: accentSolidAt(2),
      show: checkCapability('canManageFinance'),
    },
    {
      title: 'New Invoice',
      description: 'Generate a billing invoice',
      href: '/finance?tab=invoices&create=1',
      icon: Receipt,
      tone: accentSolidAt(4),
      show: checkCapability('canManageFinance'),
    },
  ]
  return actions.filter((action) => action.show)
})

const iconBtnClass =
  'relative size-9 shrink-0 rounded-xl border border-border/80 bg-card text-muted-foreground shadow-none transition-colors duration-150 hover:bg-muted/70 hover:text-foreground dark:border-border/50'

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

function focusSearch() {
  searchInput.value?.focus()
  document.getElementById('global-search')?.focus()
}

function onSearchShortcut(event: KeyboardEvent) {
  const target = event.target as HTMLElement | null
  if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) {
    if (event.key === 'Escape') {
      ;(target as HTMLInputElement).blur()
    }
    return
  }

  const metaK = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k'
  const slash = event.key === '/' && !event.metaKey && !event.ctrlKey && !event.altKey
  if (!metaK && !slash) return

  event.preventDefault()
  focusSearch()
}

onMounted(() => window.addEventListener('keydown', onSearchShortcut))
onUnmounted(() => window.removeEventListener('keydown', onSearchShortcut))

function submitSearch() {
  const q = searchQuery.value.trim()
  if (!q) return
  if (checkCapability('canManageStudents')) {
    void router.push({ path: '/people', query: { tab: 'students', search: q } })
    return
  }
  if (checkCapability('canManageFinance')) {
    void router.push({ path: '/finance', query: { tab: 'invoices', search: q } })
    return
  }
  void router.push({ path: '/people', query: { search: q } })
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
    data-app-header
    role="banner"
    class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-border/50 bg-card/90 px-3 backdrop-blur-md supports-[backdrop-filter]:bg-card/80 dark:border-border/40 dark:bg-card/95 dark:supports-[backdrop-filter]:bg-card/85 sm:gap-4 sm:px-4"
  >
    <SidebarTrigger
      class="size-9 shrink-0 rounded-xl border border-border/80 bg-card text-muted-foreground shadow-none hover:bg-muted/60 hover:text-foreground"
      aria-label="Toggle navigation sidebar"
    />

    <form
      class="relative min-w-0 flex-1 md:max-w-xl lg:max-w-2xl"
      role="search"
      @submit.prevent="submitSearch"
    >
      <label for="global-search" class="sr-only">Search students, staff, or invoices</label>
      <Search
        class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
        aria-hidden="true"
      />
      <Input
        id="global-search"
        ref="searchInput"
        v-model="searchQuery"
        type="search"
        placeholder="Search students, staff, invoices..."
        class="h-10 rounded-xl border-border/80 bg-background pl-9 pr-16 text-base shadow-none"
        autocomplete="off"
      />
      <span
        class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 items-center gap-0.5 sm:inline-flex"
        aria-hidden="true"
      >
        <kbd class="rounded-md border border-border/80 bg-muted/50 px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">
          {{ isMac ? '⌘' : 'Ctrl' }}
        </kbd>
        <kbd class="rounded-md border border-border/80 bg-muted/50 px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">
          K
        </kbd>
      </span>
    </form>

    <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
      <DropdownMenu v-if="canSwitchSchool">
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            size="sm"
            class="mr-0.5 hidden max-w-[10rem] gap-1.5 rounded-xl border-border/80 lg:inline-flex"
            :disabled="switchingSchool || loading"
            :aria-busy="switchingSchool"
            aria-label="Switch school"
          >
            <Building2 class="size-3.5 shrink-0" aria-hidden="true" />
            <span class="truncate">{{ currentSchool?.name ?? 'School' }}</span>
            <ChevronsUpDown class="size-3.5 shrink-0 opacity-60" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-64 rounded-xl">
          <DropdownMenuLabel>Switch school</DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            v-for="item in availableSchools"
            :key="item.id"
            class="cursor-pointer"
            :disabled="item.id === user?.school_id || item.status !== 'active'"
            @click="handleSwitchSchool(item.id)"
          >
            <div class="flex min-w-0 flex-col">
              <span class="truncate font-medium">{{ item.name }}</span>
              <span class="text-xs text-muted-foreground">
                {{ item.code || `School #${item.id}` }}
                <template v-if="item.id === user?.school_id"> · current</template>
              </span>
            </div>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <DropdownMenu v-if="academicYearLabel">
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            size="sm"
            class="mr-0.5 hidden h-9 max-w-[14rem] gap-2 rounded-xl border-border/80 px-3 text-sm font-medium xl:inline-flex"
            aria-label="Select academic year"
          >
            <CalendarDays class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="truncate text-muted-foreground">Academic Year :</span>
            <span class="truncate">{{ academicYearLabel }}</span>
            <ChevronsUpDown class="size-3.5 shrink-0 opacity-60" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-52 rounded-xl">
          <DropdownMenuLabel>Academic year</DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            v-for="year in academicYearOptions"
            :key="year"
            class="cursor-pointer"
            @click="setAcademicYear(year)"
          >
            {{ year }}
            <span v-if="year === academicYearLabel" class="ml-auto text-xs text-muted-foreground">current</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <!-- Language (locale) -->
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            :variant="'outline'"
            size="icon"
            :class="iconBtnClass"
            aria-label="Language"
          >
            <span class="text-[11px] font-semibold tracking-wide" aria-hidden="true">EN</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44 rounded-xl">
          <DropdownMenuLabel>Language</DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem class="cursor-pointer" disabled>
            English
          </DropdownMenuItem>
          <DropdownMenuItem
            v-if="canManageSchoolSettings"
            class="cursor-pointer"
            @click="router.push('/settings?tab=languages')"
          >
            Manage languages
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <!-- Add New (PreSkool-style quick create) -->
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            size="icon"
            :class="iconBtnClass"
            aria-label="Add new"
            aria-haspopup="menu"
          >
            <CirclePlus class="size-4" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          align="end"
          class="w-[22rem] overflow-hidden rounded-2xl border-border/70 p-0 shadow-[var(--shadow-soft)]"
        >
          <div class="flex items-start justify-between gap-3 px-4 pb-3 pt-4">
            <div class="min-w-0 space-y-0.5">
              <p class="text-base font-semibold tracking-tight text-foreground">Add New</p>
              <p class="text-sm text-muted-foreground">Quickly create a record</p>
            </div>
            <span
              class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
              aria-hidden="true"
            >
              <Zap class="size-4" />
            </span>
          </div>

          <div
            v-if="quickAddActions.length"
            class="flex flex-col gap-0.5 px-2 pb-2"
            role="menu"
            aria-label="Quick create actions"
          >
            <button
              v-for="action in quickAddActions"
              :key="action.href"
              type="button"
              role="menuitem"
              class="flex w-full items-center gap-3 rounded-xl px-2 py-2.5 text-left outline-none transition-colors hover:bg-muted/70 focus-visible:ring-2 focus-visible:ring-ring"
              @click="router.push(action.href)"
            >
              <span
                :class="cn(
                  'flex size-10 shrink-0 items-center justify-center rounded-xl text-white shadow-sm',
                  action.tone,
                )"
                aria-hidden="true"
              >
                <component :is="action.icon" class="size-5" />
              </span>
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-foreground">{{ action.title }}</span>
                <span class="block text-sm text-muted-foreground">{{ action.description }}</span>
              </span>
            </button>
          </div>
          <p
            v-else
            class="px-4 py-6 text-center text-sm text-muted-foreground"
          >
            No create actions available for your role.
          </p>

          <div class="border-t border-border/60 bg-muted/35">
            <button
              type="button"
              class="flex w-full items-center justify-center gap-1.5 px-4 py-3 text-sm font-medium text-primary outline-none transition-colors hover:bg-muted/60 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring"
              @click="router.push('/people?tab=students&create=1')"
            >
              View all options
              <ArrowRight class="size-3.5" aria-hidden="true" />
            </button>
          </div>
        </DropdownMenuContent>
      </DropdownMenu>

      <ThemeToggle variant="outline" :class="iconBtnClass" />

      <NotificationsPanel trigger-class="rounded-xl border border-border/80 bg-card shadow-none hover:bg-muted/60" />

      <!-- Messages -->
      <Button
        v-if="canOpenMessages"
        variant="outline"
        size="icon"
        :class="cn(iconBtnClass)"
        as-child
      >
        <RouterLink to="/communications/threads" aria-label="Messages">
          <MessageSquare class="size-4" aria-hidden="true" />
          <span
            v-if="messageBadge > 0"
            class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-primary-foreground"
          >
            {{ messageBadge > 9 ? '9+' : messageBadge }}
          </span>
        </RouterLink>
      </Button>

      <!-- Apps launcher (PreSkool-style grid) -->
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            size="icon"
            :class="iconBtnClass"
            aria-label="Apps"
            aria-haspopup="menu"
          >
            <LayoutGrid class="size-4" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          align="end"
          class="w-[22rem] overflow-hidden rounded-2xl border-border/70 p-0 shadow-[var(--shadow-soft)]"
        >
          <div class="flex items-start justify-between gap-3 px-4 pb-3 pt-4">
            <div class="min-w-0 space-y-0.5">
              <p class="text-base font-semibold tracking-tight text-foreground">Apps</p>
              <p class="text-sm text-muted-foreground">Jump to a workspace app</p>
            </div>
            <Button
              v-if="canManageSchoolSettings"
              variant="ghost"
              size="icon"
              class="size-9 shrink-0 rounded-xl bg-primary/10 text-primary hover:bg-primary/15 hover:text-primary"
              aria-label="Open settings"
              @click="router.push('/settings')"
            >
              <Settings class="size-4" aria-hidden="true" />
            </Button>
          </div>

          <div
            v-if="appLinks.length"
            class="grid grid-cols-3 gap-2 px-3 pb-3"
            role="menu"
            aria-label="Workspace apps"
          >
            <button
              v-for="link in appLinks"
              :key="link.href"
              type="button"
              role="menuitem"
              class="group flex flex-col items-center gap-2 rounded-xl px-2 py-2.5 text-center outline-none transition-colors hover:bg-muted/70 focus-visible:ring-2 focus-visible:ring-ring"
              @click="router.push(link.href)"
            >
              <span
                :class="cn(
                  'flex size-11 items-center justify-center rounded-xl text-white shadow-sm transition-transform duration-150 group-hover:scale-105',
                  link.tone,
                )"
                aria-hidden="true"
              >
                <component :is="link.icon" class="size-5" />
              </span>
              <span class="text-sm font-medium text-foreground">{{ link.title }}</span>
            </button>
          </div>
          <p
            v-else
            class="px-4 py-6 text-center text-sm text-muted-foreground"
          >
            No apps available for your role.
          </p>

          <div class="border-t border-border/60 bg-muted/35">
            <button
              type="button"
              class="flex w-full items-center justify-center gap-1.5 px-4 py-3 text-sm font-medium text-primary outline-none transition-colors hover:bg-muted/60 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring"
              @click="router.push('/')"
            >
              Browse all apps
              <ArrowRight class="size-3.5" aria-hidden="true" />
            </button>
          </div>
        </DropdownMenuContent>
      </DropdownMenu>

      <!-- Profile -->
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="ghost"
            class="relative ml-0.5 size-9 rounded-xl p-0 hover:bg-transparent"
            :aria-label="`Account menu for ${displayName}`"
          >
            <Avatar class="size-9 rounded-xl">
              <AvatarImage
                v-if="user?.avatar_url"
                :src="user.avatar_url"
                :alt="displayName"
                class="rounded-xl"
              />
              <AvatarFallback class="rounded-xl text-xs font-semibold">{{ initials }}</AvatarFallback>
            </Avatar>
            <span
              class="absolute bottom-0 right-0 size-2.5 rounded-full border-2 border-card bg-chart-2"
              title="Online"
              aria-hidden="true"
            />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56 rounded-xl">
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
              v-for="item in availableSchools"
              :key="`menu-${item.id}`"
              class="cursor-pointer sm:hidden"
              :disabled="item.id === user?.school_id || item.status !== 'active'"
              @click="handleSwitchSchool(item.id)"
            >
              {{ item.name }}
            </DropdownMenuItem>
            <DropdownMenuSeparator class="sm:hidden" />
          </template>
          <DropdownMenuItem
            v-if="canViewProfile"
            class="cursor-pointer text-sm"
            @click="router.push('/profile')"
          >
            My profile
          </DropdownMenuItem>
          <DropdownMenuItem
            v-if="canManageSchoolSettings"
            class="cursor-pointer text-sm"
            @click="router.push('/settings')"
          >
            School setup
          </DropdownMenuItem>
          <DropdownMenuItem
            class="cursor-pointer text-sm text-destructive focus:text-destructive"
            @click="handleLogout"
          >
            Sign out
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </header>
</template>
