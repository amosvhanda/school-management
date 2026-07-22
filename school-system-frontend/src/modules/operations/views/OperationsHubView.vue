<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import {
  OPERATIONS_HUB_DEFAULT_TAB,
  OPERATIONS_HUB_TABS,
} from '@/modules/operations/operations-hub-tabs'
</script>

<template>
  <ModuleHub
    title="Operations"
    description="Inventory, transport, campus life, and day-to-day school operations."
    route-name="operations"
    :tabs="OPERATIONS_HUB_TABS"
    :default-tab="OPERATIONS_HUB_DEFAULT_TAB"
    aria-label="Operations sections"
  />
</template>
