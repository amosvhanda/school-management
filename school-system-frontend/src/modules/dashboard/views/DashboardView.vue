<script setup lang="ts">
import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { getStaffDashboardVariant } from '@/lib/role-dashboard'
import { lazy } from '@/lib/lazy'

const AdminDashboardPanel = lazy(() => import('@/modules/dashboard/views/panels/AdminDashboardPanel.vue'))
const TeacherDashboardPanel = lazy(() => import('@/modules/dashboard/views/panels/TeacherDashboardPanel.vue'))
const FinanceDashboardPanel = lazy(() => import('@/modules/dashboard/views/panels/FinanceDashboardPanel.vue'))
const AccountsDashboardPanel = lazy(() => import('@/modules/dashboard/views/panels/AccountsDashboardPanel.vue'))
const ExamOfficerDashboardPanel = lazy(() => import('@/modules/dashboard/views/panels/ExamOfficerDashboardPanel.vue'))

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
