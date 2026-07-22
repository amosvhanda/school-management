<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import { HR_HUB_DEFAULT_TAB, HR_HUB_TABS } from '@/modules/hr/hr-hub-tabs'
</script>

<template>
  <ModuleHub
    title="HR & Compliance"
    description="Leave, discipline, policies, and audit activity in one place."
    route-name="hr"
    :tabs="HR_HUB_TABS"
    :default-tab="HR_HUB_DEFAULT_TAB"
    aria-label="HR and compliance sections"
  />
</template>
