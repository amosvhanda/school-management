<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { RouterView, useRoute } from 'vue-router'
import AppHeader from '@/components/app/AppHeader.vue'
import AppSidebar from '@/components/app/AppSidebar.vue'
import LicenseBanner from '@/components/app/LicenseBanner.vue'
import SkipToContent from '@/components/app/SkipToContent.vue'
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar'
import { useUiStore } from '@/stores/ui.store'
import type { NavGroup } from '@/types/navigation'

defineProps<{
  navigation: NavGroup[]
  title: string
  showLicenseBanner?: boolean
}>()

const route = useRoute()
const uiStore = useUiStore()
const { sidebarOpen } = storeToRefs(uiStore)
</script>

<template>
  <SidebarProvider v-model:open="sidebarOpen" class="overflow-x-hidden">
    <SkipToContent />
    <AppSidebar :navigation="navigation" :title="title" />
    <SidebarInset id="main-content" tabindex="-1" class="min-w-0 outline-none">
      <LicenseBanner v-if="showLicenseBanner" />
      <AppHeader />
      <div class="page-canvas flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">
        <!-- Remount when route name changes so shared RegistryListPage does not keep prior list rows. -->
        <RouterView :key="String(route.name ?? route.path)" />
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
