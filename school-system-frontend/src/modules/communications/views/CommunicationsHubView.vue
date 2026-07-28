<script setup lang="ts">
import { onMounted, ref } from 'vue'
import ModuleHub from '@/components/layout/ModuleHub.vue'
import {
  COMMUNICATIONS_HUB_DEFAULT_TAB,
  COMMUNICATIONS_HUB_TABS,
} from '@/modules/communications/communications-hub-tabs'
import { commsApi } from '@/services/api.service'

const badgeCounts = ref<Record<string, number>>({})

async function loadBadges() {
  try {
    const data = await commsApi.unreadCount()
    const count = Number(data?.unread_threads ?? 0)
    badgeCounts.value = count > 0 ? { messages: count } : {}
  } catch {
    badgeCounts.value = {}
  }
}

onMounted(() => {
  void loadBadges()
})
</script>

<template>
  <ModuleHub
    title="Communications"
    description="Announcements and message threads for the school community."
    route-name="communications"
    :tabs="COMMUNICATIONS_HUB_TABS"
    :default-tab="COMMUNICATIONS_HUB_DEFAULT_TAB"
    :badge-counts="badgeCounts"
    aria-label="Communications sections"
  />
</template>
