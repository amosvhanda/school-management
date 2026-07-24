<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import { TEACHING_HUB_DEFAULT_TAB, TEACHING_HUB_TABS } from '@/modules/teaching/teaching-hub-tabs'
</script>

<template>
  <ModuleHub
    title="Teaching"
    description="Pick a class, then mark, teach, or set work. Register and gradebook stay in the sidebar."
    route-name="teaching"
    nav-layout="sidebar"
    :tabs="TEACHING_HUB_TABS"
    :default-tab="TEACHING_HUB_DEFAULT_TAB"
    aria-label="Teaching sections"
  />
</template>
