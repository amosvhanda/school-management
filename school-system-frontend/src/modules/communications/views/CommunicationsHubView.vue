<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import {
  COMMUNICATIONS_HUB_DEFAULT_TAB,
  COMMUNICATIONS_HUB_TABS,
} from '@/modules/communications/communications-hub-tabs'
</script>

<template>
  <ModuleHub
    title="Communications"
    description="Announcements and message threads for the school community."
    route-name="communications"
    :tabs="COMMUNICATIONS_HUB_TABS"
    :default-tab="COMMUNICATIONS_HUB_DEFAULT_TAB"
    aria-label="Communications sections"
  />
</template>
