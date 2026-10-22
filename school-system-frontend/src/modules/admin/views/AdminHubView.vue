<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import { ADMIN_HUB_DEFAULT_TAB, ADMIN_HUB_TABS } from '@/modules/admin/admin-hub-tabs'
</script>

<template>
  <ModuleHub
    title="Administration"
    description="User accounts, roles, and school subscription."
    route-name="admin"
    :tabs="ADMIN_HUB_TABS"
    :default-tab="ADMIN_HUB_DEFAULT_TAB"
    aria-label="Administration sections"
  />
</template>
