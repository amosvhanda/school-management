<script setup lang="ts">
import { computed, defineAsyncComponent, ref, shallowRef, watch } from 'vue'
import type { Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageShell from '@/components/layout/PageShell.vue'
import WorkspaceCard from '@/components/layout/WorkspaceCard.vue'
import ModuleHubContent from '@/components/layout/ModuleHubContent.vue'
import RegistrySection from '@/modules/shared/RegistrySection.vue'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { cn } from '@/lib/utils'
import { canAccessNavItem } from '@/lib/permissions'
import { useAuth } from '@/composables/useAuth'
import {
  groupModuleHubTabs,
  sectionsForHubTab,
  hubLocation,
  shouldAutoCreateSection,
  type ModuleHubTab,
} from '@/lib/module-hub'

const props = withDefaults(
  defineProps<{
    title: string
    description: string
    routeName: string
    tabs: ModuleHubTab[]
    defaultTab?: string
    ariaLabel?: string
    /**
     * Navigation chrome:
     * - pills: horizontal chip strip (default — admin Finance/People/HR/etc.)
     * - sidebar: grouped side nav (opt-in — Teaching workspace only)
     */
    navLayout?: 'pills' | 'sidebar'
  }>(),
  {
    defaultTab: 'overview',
    ariaLabel: 'Module sections',
    navLayout: 'pills',
  },
)

const route = useRoute()
const router = useRouter()
const { user } = useAuth()

const visibleTabs = computed(() =>
  props.tabs.filter((tab) => canAccessNavItem(user.value, tab.capability)),
)

const hasAccess = computed(() => visibleTabs.value.length > 0)

const useSidebarNav = computed(() => props.navLayout === 'sidebar')

const tabGroups = computed(() => groupModuleHubTabs(visibleTabs.value))

const primaryTabs = computed(() =>
  visibleTabs.value.filter((tab) => tab.priority !== 'secondary'),
)

const secondaryTabs = computed(() =>
  visibleTabs.value.filter((tab) => tab.priority === 'secondary'),
)

const secondaryGroups = computed(() => groupModuleHubTabs(secondaryTabs.value))

const usePriorityNav = computed(
  () => useSidebarNav.value && secondaryTabs.value.length > 0,
)

const moreExpanded = ref(false)

const activeTabId = computed(() => {
  if (!hasAccess.value) return ''
  const raw = String(Array.isArray(route.query.tab) ? route.query.tab[0] : route.query.tab ?? '')
  if (raw && visibleTabs.value.some((tab) => tab.id === raw)) return raw
  if (props.defaultTab && visibleTabs.value.some((tab) => tab.id === props.defaultTab)) {
    return props.defaultTab
  }
  return visibleTabs.value[0]?.id ?? props.defaultTab
})

watch(
  activeTabId,
  (id) => {
    if (secondaryTabs.value.some((tab) => tab.id === id)) {
      moreExpanded.value = true
    }
  },
  { immediate: true },
)

const activeTab = computed(() =>
  visibleTabs.value.find((tab) => tab.id === activeTabId.value) ?? visibleTabs.value[0],
)

const activeSections = computed(() => sectionsForHubTab(activeTab.value))
const usesRegistry = computed(() => activeSections.value.length > 0 && !activeTab.value?.component && !activeTab.value?.panel)

const asyncCache = new Map<string, Component>()
const asyncView = shallowRef<Component | null>(null)

watch(
  activeTab,
  (tab) => {
    if (!tab?.component) {
      asyncView.value = null
      return
    }
    let cached = asyncCache.get(tab.id)
    if (!cached) {
      cached = defineAsyncComponent(tab.component)
      asyncCache.set(tab.id, cached)
    }
    asyncView.value = cached
  },
  { immediate: true },
)

function selectTab(tabId: string) {
  // Intentional: switching hub tabs clears list filters (status, class_id, etc.)
  // so filters from one module do not bleed into another.
  void router.replace(
    hubLocation(props.routeName, tabId, props.defaultTab, {
      query: {},
    }),
  )
}

watch(
  visibleTabs,
  (tabs) => {
    if (!tabs.length) return
    const raw = String(Array.isArray(route.query.tab) ? route.query.tab[0] : route.query.tab ?? '')
    if (raw && !tabs.some((tab) => tab.id === raw)) {
      selectTab(tabs[0].id)
    }
  },
  { immediate: true },
)

function shouldAutoCreate(section: { listKey: string }, index: number) {
  return shouldAutoCreateSection(section, index, route.query)
}
</script>

<template>
  <PageShell :title="title" :description="description" max-width="wide">
    <template v-if="$slots.actions" #actions>
      <slot name="actions" />
    </template>

    <div v-if="!hasAccess" class="rounded-2xl border border-border/60 bg-muted/20 px-6 py-12 text-center" role="alert">
      <p class="text-sm font-medium text-foreground">You do not have access to this section.</p>
      <p class="mt-2 text-sm text-muted-foreground">
        Ask an administrator if you need permission for this area.
      </p>
    </div>

    <div v-else-if="useSidebarNav" class="grid gap-6 lg:grid-cols-[13.5rem_minmax(0,1fr)] xl:grid-cols-[15rem_minmax(0,1fr)]">
      <!-- Mobile: primary + more select -->
      <div class="space-y-2 lg:hidden">
        <label for="module-hub-section" class="text-sm font-medium text-foreground">
          Section
        </label>
        <Select
          :model-value="activeTabId"
          @update:model-value="(value) => value && selectTab(String(value))"
        >
          <SelectTrigger id="module-hub-section" class="w-full">
            <SelectValue placeholder="Choose a section" />
          </SelectTrigger>
          <SelectContent>
            <template v-if="usePriorityNav">
              <SelectGroup>
                <SelectLabel>Daily</SelectLabel>
                <SelectItem
                  v-for="tab in primaryTabs"
                  :key="tab.id"
                  :value="tab.id"
                >
                  {{ tab.title }}
                </SelectItem>
              </SelectGroup>
              <SelectGroup
                v-for="group in secondaryGroups"
                :key="group.label || 'more'"
              >
                <SelectLabel>{{ group.label || 'More' }}</SelectLabel>
                <SelectItem
                  v-for="tab in group.tabs"
                  :key="tab.id"
                  :value="tab.id"
                >
                  {{ tab.title }}
                </SelectItem>
              </SelectGroup>
            </template>
            <template v-else>
              <SelectGroup v-for="group in tabGroups" :key="group.label || 'main'">
                <SelectLabel v-if="group.label">{{ group.label }}</SelectLabel>
                <SelectItem
                  v-for="tab in group.tabs"
                  :key="tab.id"
                  :value="tab.id"
                >
                  {{ tab.title }}
                </SelectItem>
              </SelectGroup>
            </template>
          </SelectContent>
        </Select>
      </div>

      <!-- Desktop: primary actions + collapsed More -->
      <nav
        class="hidden lg:block"
        :aria-label="ariaLabel"
      >
        <div class="sticky top-20 space-y-1 rounded-2xl border border-border/60 bg-muted/20 p-2.5">
          <template v-if="usePriorityNav">
            <button
              v-for="tab in primaryTabs"
              :key="tab.id"
              type="button"
              :class="cn(
                'flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                activeTabId === tab.id
                  ? 'bg-background text-foreground shadow-sm'
                  : 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
              )"
              :aria-current="activeTabId === tab.id ? 'page' : undefined"
              @click="selectTab(tab.id)"
            >
              <component :is="tab.icon" class="size-4 shrink-0" aria-hidden="true" />
              <span class="truncate">{{ tab.title }}</span>
            </button>

            <details
              class="group pt-2"
              :open="moreExpanded"
              @toggle="moreExpanded = ($event.target as HTMLDetailsElement).open"
            >
              <summary
                class="cursor-pointer list-none rounded-xl px-2.5 py-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase transition-colors hover:bg-background/70 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring [&::-webkit-details-marker]:hidden"
              >
                <span class="flex items-center justify-between gap-2">
                  More
                  <span class="text-[10px] font-medium normal-case tracking-normal text-muted-foreground/80">
                    {{ secondaryTabs.length }}
                  </span>
                </span>
              </summary>
              <div class="mt-1 space-y-3 border-t border-border/50 pt-2">
                <div
                  v-for="group in secondaryGroups"
                  :key="group.label || 'more'"
                  class="space-y-1"
                >
                  <p
                    v-if="group.label"
                    class="px-2.5 pt-0.5 text-[10px] font-semibold tracking-wide text-muted-foreground/80 uppercase"
                  >
                    {{ group.label }}
                  </p>
                  <button
                    v-for="tab in group.tabs"
                    :key="tab.id"
                    type="button"
                    :class="cn(
                      'flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                      activeTabId === tab.id
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
                    )"
                    :aria-current="activeTabId === tab.id ? 'page' : undefined"
                    @click="selectTab(tab.id)"
                  >
                    <component :is="tab.icon" class="size-4 shrink-0" aria-hidden="true" />
                    <span class="truncate">{{ tab.title }}</span>
                  </button>
                </div>
              </div>
            </details>
          </template>

          <template v-else>
            <div
              v-for="group in tabGroups"
              :key="group.label || 'main'"
              class="space-y-1"
            >
              <p
                v-if="group.label"
                class="px-2.5 pt-1 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
              >
                {{ group.label }}
              </p>
              <button
                v-for="tab in group.tabs"
                :key="tab.id"
                type="button"
                :class="cn(
                  'flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                  activeTabId === tab.id
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
                )"
                :aria-current="activeTabId === tab.id ? 'page' : undefined"
                @click="selectTab(tab.id)"
              >
                <component :is="tab.icon" class="size-4 shrink-0" aria-hidden="true" />
                <span class="truncate">{{ tab.title }}</span>
              </button>
            </div>
          </template>
        </div>
      </nav>

      <div class="min-w-0 space-y-4">
        <div v-if="$slots.tools" class="flex flex-wrap items-center gap-2">
          <slot name="tools" />
        </div>

        <ModuleHubContent>
          <slot
            v-if="activeTab?.panel"
            name="panel"
            :panel="activeTab.panel"
            :tab="activeTab"
          />

          <component
            :is="asyncView"
            v-else-if="asyncView"
            :key="activeTabId"
          />

          <WorkspaceCard
            v-else-if="usesRegistry && activeTab"
            :title="activeTab.title"
            :description="activeTab.description"
          >
            <div class="space-y-8">
              <section
                v-for="(section, index) in activeSections"
                :key="`${activeTab.id}-${section.listKey}`"
                class="space-y-3"
              >
                <div v-if="section.title || activeSections.length > 1" class="space-y-1">
                  <h3 class="text-base font-semibold text-foreground">
                    {{ section.title }}
                  </h3>
                  <p v-if="section.description" class="text-sm text-muted-foreground">
                    {{ section.description }}
                  </p>
                </div>
                <RegistrySection
                  :list-key="section.listKey"
                  :auto-create="shouldAutoCreate(section, index)"
                />
              </section>
            </div>
          </WorkspaceCard>

          <p
            v-else
            class="text-sm text-muted-foreground"
            role="status"
          >
            This section is not available.
          </p>
        </ModuleHubContent>
      </div>
    </div>

    <div v-else class="space-y-6">
      <div v-if="$slots.tools" class="flex flex-wrap items-center gap-2">
        <slot name="tools" />
      </div>

      <section class="overflow-x-auto rounded-2xl border border-border/60 bg-muted/30 p-2">
        <nav class="flex min-w-max items-center gap-2" :aria-label="ariaLabel">
          <template v-for="(group, groupIndex) in tabGroups" :key="group.label || `group-${groupIndex}`">
            <span
              v-if="group.label && groupIndex > 0"
              class="mx-1 h-6 w-px shrink-0 bg-border"
              aria-hidden="true"
            />
            <span
              v-if="group.label"
              class="px-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
            >
              {{ group.label }}
            </span>
            <button
              v-for="tab in group.tabs"
              :key="tab.id"
              type="button"
              :class="cn(
                'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                activeTabId === tab.id
                  ? 'bg-background text-foreground shadow-sm'
                  : 'text-muted-foreground hover:bg-background/80 hover:text-foreground',
              )"
              :aria-current="activeTabId === tab.id ? 'page' : undefined"
              @click="selectTab(tab.id)"
            >
              <component :is="tab.icon" class="size-4" aria-hidden="true" />
              {{ tab.title }}
            </button>
          </template>
        </nav>
      </section>

      <ModuleHubContent>
        <slot
          v-if="activeTab?.panel"
          name="panel"
          :panel="activeTab.panel"
          :tab="activeTab"
        />

        <component
          :is="asyncView"
          v-else-if="asyncView"
          :key="activeTabId"
        />

        <WorkspaceCard
          v-else-if="usesRegistry && activeTab"
          :title="activeTab.title"
          :description="activeTab.description"
        >
          <div class="space-y-8">
            <section
              v-for="(section, index) in activeSections"
              :key="`${activeTab.id}-${section.listKey}`"
              class="space-y-3"
            >
              <div v-if="section.title || activeSections.length > 1" class="space-y-1">
                <h3 class="text-base font-semibold text-foreground">
                  {{ section.title }}
                </h3>
                <p v-if="section.description" class="text-sm text-muted-foreground">
                  {{ section.description }}
                </p>
              </div>
              <RegistrySection
                :list-key="section.listKey"
                :auto-create="shouldAutoCreate(section, index)"
              />
            </section>
          </div>
        </WorkspaceCard>

        <p
          v-else
          class="text-sm text-muted-foreground"
          role="status"
        >
          This section is not available.
        </p>
      </ModuleHubContent>
    </div>
  </PageShell>
</template>
