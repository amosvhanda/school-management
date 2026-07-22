<script setup lang="ts">
import { computed, defineAsyncComponent, shallowRef, watch } from 'vue'
import type { Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageShell from '@/components/layout/PageShell.vue'
import WorkspaceCard from '@/components/layout/WorkspaceCard.vue'
import ModuleHubContent from '@/components/layout/ModuleHubContent.vue'
import RegistrySection from '@/modules/shared/RegistrySection.vue'
import { cn } from '@/lib/utils'
import { canAccessNavItem } from '@/lib/permissions'
import { useAuth } from '@/composables/useAuth'
import {
  sectionsForHubTab,
  hubLocation,
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
  }>(),
  {
    defaultTab: 'overview',
    ariaLabel: 'Module sections',
  },
)

const route = useRoute()
const router = useRouter()
const { user } = useAuth()

const visibleTabs = computed(() =>
  props.tabs.filter((tab) => canAccessNavItem(user.value, tab.capability)),
)

const activeTabId = computed(() => {
  const raw = String(Array.isArray(route.query.tab) ? route.query.tab[0] : route.query.tab ?? '')
  if (raw && visibleTabs.value.some((tab) => tab.id === raw)) return raw
  return visibleTabs.value[0]?.id ?? props.defaultTab
})

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
  void router.replace(
    hubLocation(props.routeName, tabId, props.defaultTab, {
      query: Object.fromEntries(
        Object.entries(route.query).filter(([key]) => key !== 'tab' && key !== 'create'),
      ),
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
</script>

<template>
  <PageShell :title="title" :description="description" max-width="wide">
    <template v-if="$slots.actions" #actions>
      <slot name="actions" />
    </template>

    <div class="space-y-6">
      <section class="overflow-x-auto rounded-2xl border border-border/60 bg-muted/30 p-2">
        <nav class="flex min-w-max items-center gap-2" :aria-label="ariaLabel">
          <button
            v-for="tab in visibleTabs"
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
                :auto-create="route.query.create === '1' && index === 0"
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
