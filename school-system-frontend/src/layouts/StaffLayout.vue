<script setup lang="ts">
import { computed } from 'vue'
import DashboardShell from '@/components/app/DashboardShell.vue'
import {
  accountsNavigation,
  adminNavigation,
  examinationOfficerNavigation,
  financeNavigation,
  staffNavigation,
  teacherNavigation,
} from '@/lib/navigation'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()

const navigation = computed(() => {
  switch (user.value?.role) {
    case 'teacher':
      return teacherNavigation
    case 'finance':
      return financeNavigation
    case 'accounts':
      return accountsNavigation
    case 'admin':
      return adminNavigation
    case 'examination_officer':
      return examinationOfficerNavigation
    default:
      return staffNavigation
  }
})
</script>

<template>
  <DashboardShell
    :navigation="navigation"
    title="School ERP"
    show-license-banner
  />
</template>
