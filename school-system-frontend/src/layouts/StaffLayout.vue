<script setup lang="ts">
import { computed } from 'vue'
import DashboardShell from '@/components/app/DashboardShell.vue'
import { accountsNavigation, adminNavigation, financeNavigation, staffNavigation, teacherNavigation } from '@/lib/navigation'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()

const navigation = computed(() =>
  user.value?.role === 'teacher'
    ? teacherNavigation
    : user.value?.role === 'finance'
      ? financeNavigation
    : user.value?.role === 'accounts'
      ? accountsNavigation
    : user.value?.role === 'admin'
      ? adminNavigation
      : staffNavigation,
)
</script>

<template>
  <DashboardShell
    :navigation="navigation"
    title="School ERP"
    show-license-banner
  />
</template>
