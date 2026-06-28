<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import * as icons from 'lucide-vue-next'
import { ArrowUpRight, Search } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/composables/useAuth'
import { canAccessNavItem } from '@/lib/permissions'
import type { DashboardModuleGroup } from '@/lib/dashboard-modules'

const props = withDefaults(
  defineProps<{
    groups: DashboardModuleGroup[]
    title?: string
    description?: string
    /** Skip capability checks (e.g. parent/platform modules) */
    skipPermissionFilter?: boolean
  }>(),
  {
    title: 'All modules',
    description: 'Every area of the system you can access',
    skipPermissionFilter: false,
  },
)

const { user } = useAuth()
const search = ref('')

function resolveIcon(name: string) {
  return (icons as Record<string, unknown>)[name] as typeof icons.Circle ?? icons.Circle
}

const visibleGroups = computed(() => {
  const query = search.value.trim().toLowerCase()

  return props.groups
    .map((group) => ({
      ...group,
      modules: group.modules.filter((module) => {
        if (!props.skipPermissionFilter && !canAccessNavItem(user.value, module.capability)) {
          return false
        }
        if (!query) return true
        return (
          module.title.toLowerCase().includes(query)
          || module.description.toLowerCase().includes(query)
        )
      }),
    }))
    .filter((group) => group.modules.length > 0)
})

const totalModules = computed(() =>
  visibleGroups.value.reduce((sum, group) => sum + group.modules.length, 0),
)
</script>

<template>
  <section v-if="totalModules > 0" aria-labelledby="dashboard-modules-title" class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h2 id="dashboard-modules-title" class="text-lg font-semibold tracking-tight text-foreground">{{ title }}</h2>
        <p class="text-sm text-muted-foreground">{{ description }}</p>
      </div>
      <div class="w-full max-w-xs space-y-2">
        <Label for="module-search" class="sr-only">Search modules</Label>
        <div class="relative">
          <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
          />
          <Input
            id="module-search"
            v-model="search"
            type="search"
            placeholder="Search modules…"
            class="h-10 pl-9 text-sm"
            autocomplete="off"
          />
        </div>
      </div>
    </div>

    <div class="space-y-8">
      <div v-for="group in visibleGroups" :key="group.label" class="space-y-3">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground/90">
          {{ group.label }}
        </h3>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <RouterLink
            v-for="module in group.modules"
            :key="module.href"
            :to="module.href"
            class="group rounded-xl border bg-card text-card-foreground shadow-sm flex items-start gap-3 p-4 transition-all hover:border-muted-foreground/30 hover:shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            <div
              class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground transition-colors group-hover:bg-primary/10 group-hover:text-primary"
            >
              <component :is="resolveIcon(module.icon)" class="size-4" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-medium leading-snug group-hover:text-primary transition-colors">{{ module.title }}</p>
                <ArrowUpRight
                  class="size-3.5 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                  aria-hidden="true"
                />
              </div>
              <p class="mt-0.5 text-xs text-muted-foreground line-clamp-2 leading-normal">{{ module.description }}</p>
            </div>
          </RouterLink>
        </div>
      </div>
    </div>

    <p v-if="search && totalModules === 0" class="text-sm text-muted-foreground italic" role="status">
      No modules match “{{ search }}”.
    </p>
  </section>
</template>
