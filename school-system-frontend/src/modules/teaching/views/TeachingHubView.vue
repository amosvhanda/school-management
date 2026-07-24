<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import { TEACHING_HUB_DEFAULT_TAB, TEACHING_HUB_TABS } from '@/modules/teaching/teaching-hub-tabs'
</script>

<template>
  <ModuleHub
    title="Teaching"
    description="Pick a class and get to work. Attendance and gradebook stay in the main sidebar when you need them."
    route-name="teaching"
    nav-layout="sidebar"
    :tabs="TEACHING_HUB_TABS"
    :default-tab="TEACHING_HUB_DEFAULT_TAB"
    aria-label="Teaching sections"
  />
</template>
