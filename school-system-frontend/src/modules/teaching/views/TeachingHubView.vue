<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import { TEACHING_HUB_DEFAULT_TAB, TEACHING_HUB_TABS } from '@/modules/teaching/teaching-hub-tabs'
</script>

<template>
  <ModuleHub
    title="Teaching workspace"
    description="Classes, lessons, resources, assessment, leave, and AI tools for your teaching load."
    route-name="teaching"
    :tabs="TEACHING_HUB_TABS"
    :default-tab="TEACHING_HUB_DEFAULT_TAB"
    aria-label="Teaching sections"
  />
</template>
