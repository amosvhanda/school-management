<script setup lang="ts">
import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { getStaffDashboardVariant } from '@/lib/role-dashboard'
import AdminDashboardPanel from '@/modules/dashboard/views/panels/AdminDashboardPanel.vue'
import TeacherDashboardPanel from '@/modules/dashboard/views/panels/TeacherDashboardPanel.vue'
import FinanceDashboardPanel from '@/modules/dashboard/views/panels/FinanceDashboardPanel.vue'
import AccountsDashboardPanel from '@/modules/dashboard/views/panels/AccountsDashboardPanel.vue'
import ExamOfficerDashboardPanel from '@/modules/dashboard/views/panels/ExamOfficerDashboardPanel.vue'

const { user } = useAuth()

const variant = computed(() => getStaffDashboardVariant(user.value?.role))

const panel = computed(() => {
  switch (variant.value) {
    case 'teacher':
      return TeacherDashboardPanel
    case 'finance':
      return FinanceDashboardPanel
    case 'accounts':
      return AccountsDashboardPanel
    case 'examination_officer':
      return ExamOfficerDashboardPanel
    default:
      return AdminDashboardPanel
  }
})
</script>

<template>
  <component :is="panel" :key="variant" />
</template>
