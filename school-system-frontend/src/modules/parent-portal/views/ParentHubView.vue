<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import {
  PARENT_HUB_DEFAULT_TAB,
  PARENT_HUB_TABS,
} from '@/modules/parent-portal/parent-hub-tabs'
</script>

<template>
  <ModuleHub
    title="School life"
    description="Messages, notices, consent forms, store, and trips."
    route-name="portal-hub"
    :tabs="PARENT_HUB_TABS"
    :default-tab="PARENT_HUB_DEFAULT_TAB"
    aria-label="Parent portal sections"
  />
</template>
