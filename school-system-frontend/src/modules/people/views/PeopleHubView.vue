<script setup lang="ts">
import ModuleHub from '@/components/layout/ModuleHub.vue'
import { PEOPLE_HUB_DEFAULT_TAB, PEOPLE_HUB_TABS } from '@/modules/people/people-hub-tabs'
</script>

<template>
  <ModuleHub
    title="People"
    description="Students, teachers, guardians, and enrollment."
    route-name="people"
    :tabs="PEOPLE_HUB_TABS"
    :default-tab="PEOPLE_HUB_DEFAULT_TAB"
    aria-label="People sections"
  />
</template>
